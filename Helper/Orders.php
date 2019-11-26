<?php

namespace Cocote\Feed\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\App\ObjectManager;
use \Magento\Store\Model\StoreManagerInterface;

class Orders extends AbstractHelper
{

    protected $output;
    protected $scopeConfig;
    protected $storeManager;
    protected $configInterface;
    protected $moduleList;
    protected $logger;
    protected $dir;


    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Filesystem\DirectoryList $dir,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Module\ModuleList $moduleList,
        Context $context
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->configInterface = $configInterface;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->dir = $dir;
        $this->logger = $logger;
        $this->logger->pushHandler(new \Monolog\Handler\StreamHandler($this->dir->getRoot() . '/var/log/cocote.log'));

        parent::__construct($context);
    }

    public function getOrdersFromCocote()
    {
        try {
            if (!function_exists('curl_version')) {
                throw new Exception('no curl');
            }
            $key = $this->getConfigValue('cocote/catalog/shop_key');
            $shopId = $this->getConfigValue('cocote/catalog/shop_id');

            $shopId = 55;
            $key = 'd2e79d74a361f1d87ffdc519e0d2b941cbef0f395a779fb8bfcdc32a8e8b963c9205b40cfc09272a0aa262cc6bbfe6e8919e88e1d34a66417717775897c1eafb';

            $headers = [
                "X-Shop-Id: " . $shopId,
                "X-Secret-Key: " . $key,
                'X-Site-Version: Magento ' . $this->getMagentoVersion(),
                'X-Plugin-Version:' . $this->getModuleVersion(),
            ];

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://fr.cocote.com/api/shops/v2/get-last-orders",
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => $headers,
            ]);

            $result = curl_exec($curl);
            $resultArray = json_decode($result, true);

            curl_close($curl);
            return $resultArray;
        } catch (Exception $e) {
            $this->logger->log(100, $e->getMessage());
        }
    }


    public function createOrder($data)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $store = $this->storeManager->getStore();

        try {
            $quote = $objectManager->get('\Magento\Quote\Model\QuoteFactory')->create();
            $quote->setStore($store);
            $quote->setCurrency();
            $quote->setData($data['customer_data']);
            $quote->setCocote(1);

            foreach ($data['products'] as $request) {
                $quote->addProduct($request['prod'], $request['params']);
            }

            $quote->getBillingAddress()->addData($data['billing_address']);
            $quote->getShippingAddress()->addData($data['shipping_address']);

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod('cocote_cocote'); //shipping method

            $quote->setPaymentMethod('cocote'); //payment method
            $quote->setInventoryProcessed(false);
            $quote->save();

            $quote->getPayment()->importData(['method' => 'cocote']);
            $quote->collectTotals()->save();

            $order = $objectManager->get('\Magento\Quote\Model\QuoteManagement')->submit($quote);
            $order->setEmailSent(0);
            echo 'Order Id:' . $order->getRealOrderId();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function testOrderCreate()
    {
        $ordersNew=$this->getOrdersFromCocote();
        if(isset($ordersNew['orders'])) {
            foreach($ordersNew['orders'] as $orderData) {
                print_r($orderData);
                $data=$this->prepareOrderData($orderData);
                print_r($data);
                //$this->createOrder($data);
            }
        }
        else {
            $this->logger->log(100, print_r($ordersNew['errors'],true));
        }
    }



    public function prepareOrderData($orderData)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $data = [];

        $customerData = [
            'customer_is_guest' => 1,
            'customer_firstname' => $orderData['billing_address']['billing_firstname'],
            'customer_lastname' => $orderData['billing_address']['billing_lastname'],
            'customer_email' => $orderData['billing_address']['billing_email']
        ];
        $data['customer_data'] = $customerData;

        $data['billing_address'] = [
            'firstname' => $orderData['billing_address']['billing_firstname'],
            'lastname' => $orderData['billing_address']['billing_lastname'],
            'company' => $orderData['billing_address']['billing_company_name'],
            'street' => [$orderData['billing_address']['billing_address1'],$orderData['billing_address']['billing_address2']],
            'city' => $orderData['billing_address']['billing_city'],
            'country_id' => $orderData['billing_address']['billing_country_code'],
//            'region' => 'Alaska',
//            'region_id' => '2',
            'postcode' => $orderData['billing_address']['billing_postcode'],
            'telephone' => $orderData['billing_address']['billing_phone']
        ];

        $data['shipping_address'] = [
            'firstname' => $orderData['delivery_address']['delivery_firstname'],
            'lastname' => $orderData['delivery_address']['delivery_lastname'],
            'company' => $orderData['delivery_address']['delivery_company_name'],
            'street' => [$orderData['delivery_address']['delivery_address1'],$orderData['delivery_address']['delivery_address2']],
            'city' => $orderData['delivery_address']['delivery_city'],
            'country_id' => $orderData['delivery_address']['delivery_country_code'],
//            'region' => 'Alaska',
//            'region_id' => '2',
            'postcode' => $orderData['delivery_address']['delivery_postcode'],
            'telephone' => $orderData['delivery_address']['delivery_phone'],
            'cocote' => 'true',
            'shipping_cost' => $orderData['shipping_costs_vat'],
        ];

        foreach($orderData['products'] as $product) {
            if(isset($product['variation_id'])) {
                $productIds[]=$this->getConfigurableProductsData($product);
            }
            else {
                $simpleProduct = $objectManager->get('\Magento\Catalog\Model\ProductRepository')->getById($product['id']);
                $params = array(
                    'product' => $simpleProduct->getId(),
                    'qty' => $product['quantity'],
                    'price' => $product['unit_price_vat'],
                );
                $request = new Varien_Object();
                $request->setData($params);
                $productIds[] = ['prod' => $simpleProduct, 'params' => $request];
            }
        }

        $data['products'] = $productIds;
        return $data;
    }

    public function getConfigurableProductsData($product) {

        $simpleProductId = $product['variation_id'];
        $configurableId = $product['id'];
        $simpleProductId = 2;
        $configurableId = 3;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $simpleProduct = $objectManager->get('\Magento\Catalog\Model\ProductRepository')->getById($simpleProductId);
        $configurableProduct = $objectManager->get('\Magento\Catalog\Model\ProductRepository')->getById($configurableId);

        $productAttributeOptions = $configurableProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($configurableProduct);

        $options = array();

        foreach ($productAttributeOptions as $productAttribute) {
            $allValues = array_column($productAttribute['values'], 'value_index');
            $currentProductValue = $simpleProduct->getData($productAttribute['attribute_code']);
            if (in_array($currentProductValue, $allValues)) {
                $options[$productAttribute['attribute_id']] = $currentProductValue;
            }
        }
        $params = array(
            'price' => $product['unit_price_vat'],
            'product' => $configurableProduct->getId(),
            'qty' => $product['quantity'],
            'super_attribute' => $options,
        );

        $obj = new \Magento\Framework\DataObject();
        $obj->setData($params);

        $retData = ['prod' => $configurableProduct, 'params' => $obj];
        return $retData;
    }


    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getModuleVersion()
    {
        return $this->moduleList->getOne('Cocote_Feed')['setup_version'];
    }

    public function getMagentoVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();
        return $version;
    }



}