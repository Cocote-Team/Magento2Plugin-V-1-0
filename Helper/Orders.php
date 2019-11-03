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

    public function createOrder()
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();

        $firstName = 'Testfirstname';
        $lastName = 'lastname';
        $email='test@email.com';

        $address = array(
            'customer_address_id' => '',
            'firstname' => $firstName,
            'lastname' => $lastName,
            'street' => array(
                '0' => 'Customer Address 1', // this is mandatory
                '1' => 'Customer Address 2' // this is optional
            ),
            'city' => 'New York',
            'country_id' => 'US', // two letters country code
            'region' => 'New York', // can be empty '' if no region
            'region_id' => '43', // can be empty '' if no region_id
            'postcode' => '10450',
            'telephone' => '123-456-7890',
            'save_in_address_book' => 0
        );

        try {

            $quote = $objectManager->get('\Magento\Quote\Model\QuoteFactory')->create();
            $quote->setStore($store);
            $quote->setCurrency();

            $quote->setCustomerFirstname($firstName);
            $quote->setCustomerLastname($lastName);
            $quote->setCustomerEmail($email);
            $quote->setCustomerIsGuest(true);

            $productIds = array(1 => 1);
            foreach ($productIds as $productId => $qty) {
                $product = $objectManager->get('\Magento\Catalog\Model\ProductRepository')->getById($productId); // get product by product id
                $quote->addProduct($product, $qty); // add products to quote
            }

            /*
             * Set Address to quote
             */
            $quote->getBillingAddress()->addData($address);
            $quote->getShippingAddress()->addData($address);

            /*
             * Collect Rates and Set Shipping & Payment Method
             */
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod('flatrate_flatrate'); //shipping method

            $quote->setPaymentMethod('checkmo'); //payment method
            $quote->setInventoryProcessed(false);
            $quote->save();

            /*
             * Set Sales Order Payment
             */
            $quote->getPayment()->importData(['method' => 'checkmo']);
            $quote->collectTotals()->save();

            /*
             * Create Order From Quote
             */
            $order = $objectManager->get('\Magento\Quote\Model\QuoteManagement')->submit($quote);
            $order->setEmailSent(0);
            echo 'Order Id:' . $order->getRealOrderId();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

}
