<?php

namespace Cocote\Feed\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderObserver implements ObserverInterface
{
    protected $logger;
    protected $dir;
    protected $scopeConfig;
    protected $orderRepository;
    protected $helper;


    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Filesystem\DirectoryList $dir,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Cocote\Feed\Helper\Data $helper,
        OrderRepositoryInterface $OrderRepositoryInterface
    ) {
        $this->logger = $logger;
        $this->dir = $dir;
        $this->logger->pushHandler(new \Monolog\Handler\StreamHandler($this->dir->getRoot().'/var/log/cocote.log'));
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $OrderRepositoryInterface;
        $this->helper=$helper;

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        try {
            $order=$observer->getEvent()->getOrder();
            if (!($order instanceof \Magento\Framework\Model\AbstractModel)) {
                $this->logger->log(100, 'error');
                return;
            }

            if(isset($_COOKIE["Cocote-token"])) {
                $token=htmlspecialchars($_COOKIE["Cocote-token"]);
                $this->helper->saveToken($token,$order->getId());
            }
            if(!$token) {
                $token=$this->helper->getToken($order->getId());
            }

            $oldState=$order->getOrigData('state');
            $state=$order->getState();

            if ($state==$oldState) {
                return;
            }
            if(!($state=='complete' || $oldState=='complete' || $state=='processing')) {
                return;
            }

            $mappedStatuses=array('complete'=>'shipped',
                'processing'=>'paid',
                'closed' =>'refunded'

            );

            if(isset($mappedStatuses[$state])) {
                $state=$mappedStatuses[$state];
            }

            $refundedAmount=0;
            if($order->getData('base_total_refunded')) {
                $refundedAmount=$order->getData('base_total_refunded');
            }

            $items=array();
            foreach ($order->getAllVisibleItems() as $item) {
                $items[]=['id'=>$item->getSku(),'qty'=>1];
            }

            $data=[
                'orderId' => $order->getIncrementId(),
                'orderDate' => $order->getCreatedAt(),
                'orderState' => $state,
                'refundedAmount' => $refundedAmount,
                'orderAmount' => $order->getGrandTotal(),
                'priceCurrency' => 'EUR',
                'customerToken' =>$token,
                'trackingUrl'=>'',
                'products'=>$items, //id quantity array
            ];

            $dataJson = json_encode($data);

            $this->logger->log(100, print_r($data, true));

            if (!function_exists('curl_version')) {
                throw new \Exception('no curl');
            }

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'X-Shop-Id: '.$this->scopeConfig->getValue('cocote/general/shop_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'X-Secret-Key: '.$this->scopeConfig->getValue('cocote/general/shop_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'X-Site-Version: Magento '.$this->helper->getMagentoVersion(),
                'X-Plugin-Version:'.$this->helper->getModuleVersion(),
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataJson),
            ));

            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 1000);
            curl_setopt($curl, CURLOPT_URL, "https://fr.cocote.com/api/shops/v2/notify-order");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($curl);
            curl_close($curl);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        } finally {
            restore_error_handler();
        }
    }
}
