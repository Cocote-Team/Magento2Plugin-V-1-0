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
    

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Filesystem\DirectoryList $dir,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $OrderRepositoryInterface
    ) {
        $this->logger = $logger;
        $this->dir = $dir;
        $this->logger->pushHandler(new \Monolog\Handler\StreamHandler($this->dir->getRoot().'/var/log/cocote.log'));
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $OrderRepositoryInterface;
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
            $oldState=$order->getOrigData('state');
            $state=$order->getState();
            if ($state!='complete' && $oldState!='complete') {
                return;
            }

            $mappedStatuses=array('complete'=>'completed');
            if(isset($mappedStatuses[$state])) {
                $state=$mappedStatuses[$state];
            }

            $data=[
                'shopId' => $this->scopeConfig->getValue('cocote/general/shop_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'privateKey' => $this->scopeConfig->getValue('cocote/general/shop_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'email' => $order->getCustomerEmail(),
                'orderId' => $order->getIncrementId(),
                'orderState' => $state,
                'orderPrice' => $order->getGrandTotal(),
                'priceCurrency' => 'EUR',
            ];
            $this->logger->log(100, print_r($data, true));

            if (!function_exists('curl_version')) {
                throw new \Exception('no curl');
            }

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_URL, "https://fr.cocote.com/api/cashback/request");
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
