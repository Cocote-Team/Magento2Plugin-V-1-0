<?php


namespace Cocote\Feed\Api\Data;

interface ProductSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Product list.
     * @return \Cocote\Feed\Api\Data\ProductInterface[]
     */
    public function getItems();

    /**
     * Set product_id list.
     * @param \Cocote\Feed\Api\Data\ProductInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
