<?php


namespace Cocote\Feed\Model;

use Cocote\Feed\Api\Data\ProductInterface;
use Cocote\Feed\Api\Data\ProductInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class Product extends \Magento\Framework\Model\AbstractModel
{

    protected $productDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'cocote_feed_product';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ProductInterfaceFactory $productDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Cocote\Feed\Model\ResourceModel\Product $resource
     * @param \Cocote\Feed\Model\ResourceModel\Product\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ProductInterfaceFactory $productDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Cocote\Feed\Model\ResourceModel\Product $resource,
        \Cocote\Feed\Model\ResourceModel\Product\Collection $resourceCollection,
        array $data = []
    ) {
        $this->productDataFactory = $productDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve product model with product data
     * @return ProductInterface
     */
    public function getDataModel()
    {
        $productData = $this->getData();
        
        $productDataObject = $this->productDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $productDataObject,
            $productData,
            ProductInterface::class
        );
        
        return $productDataObject;
    }
}
