<?php


namespace Cocote\Feed\Model;

use Cocote\Feed\Api\ProductRepositoryInterface;
use Cocote\Feed\Api\Data\ProductSearchResultsInterfaceFactory;
use Cocote\Feed\Api\Data\ProductInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Cocote\Feed\Model\ResourceModel\Product as ResourceProduct;
use Cocote\Feed\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;

class ProductRepository implements ProductRepositoryInterface
{

    protected $resource;

    protected $productFactory;

    protected $productCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataProductFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceProduct $resource
     * @param ProductFactory $productFactory
     * @param ProductInterfaceFactory $dataProductFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceProduct $resource,
        ProductFactory $productFactory,
        ProductInterfaceFactory $dataProductFactory,
        ProductCollectionFactory $productCollectionFactory,
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataProductFactory = $dataProductFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Cocote\Feed\Api\Data\ProductInterface $product
    ) {
        /* if (empty($product->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $product->setStoreId($storeId);
        } */
        
        $productData = $this->extensibleDataObjectConverter->toNestedArray(
            $product,
            [],
            \Cocote\Feed\Api\Data\ProductInterface::class
        );
        
        $productModel = $this->productFactory->create()->setData($productData);
        
        try {
            $this->resource->save($productModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the product: %1',
                $exception->getMessage()
            ));
        }
        return $productModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($productId)
    {
        $product = $this->productFactory->create();
        $this->resource->load($product, $productId);
        if (!$product->getId()) {
            throw new NoSuchEntityException(__('Product with id "%1" does not exist.', $productId));
        }
        return $product->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->productCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Cocote\Feed\Api\Data\ProductInterface::class
        );
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Cocote\Feed\Api\Data\ProductInterface $product
    ) {
        try {
            $productModel = $this->productFactory->create();
            $this->resource->load($productModel, $product->getProductId());
            $this->resource->delete($productModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Product: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($productId)
    {
        return $this->delete($this->getById($productId));
    }
}
