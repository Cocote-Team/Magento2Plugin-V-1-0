<?php
namespace Cocote\Feed\Controller\Adminhtml\Cocote;

use Magento\Framework\Controller\ResultFactory;

class Generate extends \Magento\Backend\App\Action
{

    protected $helper;
    protected $resultRedirect;
    protected $messageManager;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Cocote\Feed\Helper\Data $helper
    ) {
        $this->helper=$helper;
        $this->resultRedirect = $result;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }


    public function execute()
    {
        try {
            $this->helper->generateFeed();
            $this->messageManager->addSuccessMessage(__("Generating done"));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
