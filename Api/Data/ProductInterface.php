<?php


namespace Cocote\Feed\Api\Data;

interface ProductInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const PRODUCT_ID = 'product_id';

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId();

    /**
     * Set product_id
     * @param string $productId
     * @return \Cocote\Feed\Api\Data\ProductInterface
     */
    public function setProductId($productId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Cocote\Feed\Api\Data\ProductExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Cocote\Feed\Api\Data\ProductExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Cocote\Feed\Api\Data\ProductExtensionInterface $extensionAttributes
    );
}
