<?php
namespace Cocote\Feed\Model\ResourceModel\Product;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'cocote_feed_product_collection';
    protected $_eventObject = 'product_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Cocote\Feed\Model\Product', 'Cocote\Feed\Model\ResourceModel\Product');
    }

}
