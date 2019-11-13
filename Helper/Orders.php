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


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        StoreManagerInterface $storeManager,

        Context $context
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->configInterface = $configInterface;
        $this->storeManager = $storeManager;

        parent::__construct($context);
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

            foreach($data['products'] as $request) {
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

    public function testOrderCreate() {
        $data=[];

        $simpleProductId=2;
        $configurableId=3;

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
            'product' => $configurableProduct->getId(),
            'qty' => 1,
            'super_attribute' => $options,
        );

        $obj = new \Magento\Framework\DataObject();
        $obj->setData($params);

        $productIds = [];
        $productIds[]=['prod'=>$configurableProduct,'params'=>$obj];

        $data['products']=$productIds;

        $customerData=[
            'customer_is_guest'=>1,
            'customer_firstname'=>'fn',
            'customer_lastname'=>'lasnt',
            'customer_email'=>'jaki@taki.pl'
        ];
        $data['customer_data']=$customerData;

        $billingAddress = array(
            'firstname' => 'first',
            'lastname' => 'lastname',
            'street' => array(
                '0' => 'Thunder River Boulevard', // required
                '1' => 'Customer Address 2' // optional
            ),
            'city' => 'Teramuggus',
            'country_id' => 'FR', // country code
            'postcode' => '99767',
            'telephone' => '123-456-7890',
        );

        $shippingAddress = array(
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'street' => array(
                '0' => 'Thunder River Boulevard', // required
                '1' => 'Customer Address 2' // optional
            ),
            'city' => 'Teramuggus',
            'country_id' => 'FR',
            'postcode' => '99767',
            'telephone' => '123-456-7890',
            'cocote' => 'true',
        );

        $data['billing_address']= $billingAddress;
        $data['shipping_address'] =$shippingAddress;
        $this->createOrder($data);
    }

}