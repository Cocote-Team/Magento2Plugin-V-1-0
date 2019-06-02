<?php


namespace Cocote\Feed\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ProductRepositoryInterface
{

    /**
     * Save Product
     * @param \Cocote\Feed\Api\Data\ProductInterface $product
     * @return \Cocote\Feed\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Cocote\Feed\Api\Data\ProductInterface $product
    );

    /**
     * Retrieve Product
     * @param string $productId
     * @return \Cocote\Feed\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($productId);

    /**
     * Retrieve Product matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Cocote\Feed\Api\Data\ProductSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Product
     * @param \Cocote\Feed\Api\Data\ProductInterface $product
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Cocote\Feed\Api\Data\ProductInterface $product
    );

    /**
     * Delete Product by ID
     * @param string $productId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($productId);
}
