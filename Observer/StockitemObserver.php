<?php

namespace Cocote\Feed\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class StockitemObserver implements ObserverInterface
{
    protected $logger;
    protected $dir;
    protected $scopeConfig;
    protected $helper;
    

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Filesystem\DirectoryList $dir,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Cocote\Feed\Helper\Data $helper
   ) {
        $this->logger = $logger;
        $this->dir = $dir;
        $this->logger->pushHandler(new \Monolog\Handler\StreamHandler($this->dir->getRoot().'/var/log/cocote.log'));
        $this->scopeConfig = $scopeConfig;
        $this->helper=$helper;

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        try {
            $item=$observer->getEvent()->getItem();

            if($item->getData('qty')!=$item->getOrigData('qty')) {
               // $this->helper->saveProductToUpdate($item->getProductId()+4);
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        } finally {
            restore_error_handler();
        }
    }
}
