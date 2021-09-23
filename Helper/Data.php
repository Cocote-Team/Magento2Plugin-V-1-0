<?php

namespace Cocote\Feed\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\App\ObjectManager;
use \Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use \Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    public $mapping=[];

    protected $productFactory;
    protected $output;
    protected $scopeConfig;
    protected $productCollectionFactory;
    protected $categoryFactory;
    protected $productVisibility;
    protected $galleryReadHandler;
    protected $storeManager;
    protected $priceHelper;
    protected $configInterface;
    protected $stockHelper;
    protected $cacheTypeList;
    protected $resource;
    protected $timeZone;
    protected $categoriesList=[];
    protected $moduleList;
    protected $stockState;
    protected $stockRegistry;
    protected $cocoteProductFactory;
    protected $cocoteDataProductFactory;
    protected $cocoteProductRepository;
    protected $searchCriteriaBuilder;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\App\State $appState,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        GalleryReadHandler $galleryReadHandler,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Cocote\Feed\Model\Data\ProductFactory $cocoteDataProductFactory,
        \Cocote\Feed\Model\ProductFactory $cocoteProductFactory,
        \Cocote\Feed\Api\ProductRepositoryInterface $cocoteProductRepository,

        Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configInterface = $configInterface;
        $this->productVisibility = $productVisibility;
        $this->productCollectionFactory=$productCollectionFactory;
        $this->categoryFactory=$categoryFactory;
        $this->galleryReadHandler = $galleryReadHandler;
        $this->storeManager=$storeManager;
        $this->priceHelper=$priceHelper;
        $this->stockHelper=$stockHelper;
        $this->stockState=$stockState;
        $this->stockRegistry=$stockRegistry;
        $this->timeZone=$timezone;
        $this->cacheTypeList = $cacheTypeList;
        $this->resource = $resource;
        $this->moduleList=$moduleList;
        $this->cocoteProductFactory=$cocoteProductFactory;
        $this->cocoteDataProductFactory=$cocoteDataProductFactory;
        $this->cocoteProductRepository=$cocoteProductRepository;

        parent::__construct($context);
    }

    public function getFileLink()
    {
        $path=$this->getConfigValue('cocote/generate/path');
        $baseUrl=$this->storeManager->getStore()->getBaseUrl();
        return $baseUrl.'pub/'.$path.'/'.$this->getFileName();
    }

    public function getFilePath()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');

        $path=$this->getConfigValue('cocote/generate/path');

        $dirPath=$directory->getPath('pub').'/'.$path;

        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        return $dirPath.'/'.$this->getFileName();
    }

    public function getFileName()
    {
        $fileName=$this->getConfigValue('cocote/generate/filename');

        if (!$fileName) {
            $fileName=$this->generateRandomString().'.xml';
            $this->configInterface
                ->saveConfig('cocote/generate/filename', $fileName, 'default', 0);
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
            $this->cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
        }
        return $fileName;
    }

    public function generateRandomString($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getProductCollection()
    {
        $storeCode=$this->getConfigValue('cocote/general/store');
        if (!$storeCode) {
            $storeCode='default';
        }

        $this->storeManager->setCurrentStore($storeCode);

        $collection = $this->productCollectionFactory->create();

        $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());

        $collection->addAttributeToSelect('price');
        $collection->addAttributeToSelect('special_price');
        $collection->addAttributeToSelect('special_price_from');
        $collection->addAttributeToSelect('special_price_to');
        $collection->addAttributeToSelect('image');
        $collection->addAttributeToSelect('meta_keyword');
        $collection->addAttributeToSelect('short_description');
        $collection->addAttributeToSelect('weight');
        $collection->addFieldToFilter('status', ['eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED]);

        $inStockOnly=$this->getConfigValue('cocote/generate/in_stock_only');
        if ($inStockOnly) {
            $this->stockHelper->addInStockFilterToCollection($collection);
        }

        foreach ($this->mapping as $attribute) {
            $collection->addAttributeToSelect($attribute);
        }
        $collection->addCategoryIds();
        return $collection;
    }

    public function generateFeed()
    {
        $filePath=$this->getFilePath();
        $store = $this->storeManager->getStore();

        $mapName=$this->getConfigValue('cocote/general/map_name');
        $mapMpn=$this->getConfigValue('cocote/general/map_mpn');
        $mapGtin=$this->getConfigValue('cocote/general/map_gtin');
        $mapDescription=$this->getConfigValue('cocote/general/map_description');
        $mapManufacturer=$this->getConfigValue('cocote/general/map_manufacturer');

        if ($mapName) {
            $this->mapping['title']=$mapName;
        }
        if ($mapMpn) {
            $this->mapping['mpn']=$mapMpn;
        }
        if ($mapGtin) {
            $this->mapping['gtin']=$mapGtin;
        }
        if ($mapDescription) {
            $this->mapping['description']=$mapDescription;
        }
        if ($mapManufacturer) {
            $this->mapping['brand']=$mapManufacturer;
        }

        $productCollection = $this->getProductCollection();

        $domtree = new \DOMDocument('1.0', 'UTF-8');

        $xmlRoot = $domtree->createElement("shop");
        $xmlRoot = $domtree->appendChild($xmlRoot);

        $generated = $domtree->createElement('generated',$this->timeZone->date()->format('Y-m-d H:i:s'));
        $generated->setAttribute('cms', 'magento');
        $version=$this->getModuleVersion();
        $generated->setAttribute('plugin_version',$version);

        $xmlRoot->appendChild($generated);

        $offers = $domtree->createElement("offers");
        $offers = $xmlRoot->appendChild($offers);

        foreach ($productCollection as $product) {
            $imageLink='';
            $imageSecondaryLink='';

            if ($product->getImage()) {
                $imageLink = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                $this->addGallery($product);
                $images = $product->getMediaGalleryImages();
                foreach ($images as $image) {
                    if ($image->getFile()==$product->getImage()) {
                        continue;
                    }
                    if ($image->getUrl() && $image->getMediaType()=='image') {
                        $imageSecondaryLink=$image->getUrl();
                        break;
                    }
                }
            }

            $currentprod = $domtree->createElement("item");
            $currentprod = $offers->appendChild($currentprod);

            $url=$product->getProductUrl();

            $currentprod->appendChild($domtree->createElement('identifier', $product->getId()));
            $currentprod->appendChild($domtree->createElement('link', $url));
            $currentprod->appendChild($domtree->createElement('keywords', $product->getData('meta_keyword')));

            if($product->getData('weight')) {
                $currentprod->appendChild($domtree->createElement('weight', $product->getData('weight')));
            }

            $descTag=$domtree->createElement('short_description');
            $descTag->appendChild($domtree->createCDATASection($product->getData('short_description')));
            $currentprod->appendChild($descTag);

            if($catName=$this->getBestCategory($product->getCategoryIds())) {
                $currentprod->appendChild($domtree->createElement('category', $catName));
            }

              
            if($product->getTypeId()=='bundle') {
                $rawPrice=$product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
                $price=$this->priceHelper->currency($rawPrice, true, false);
            } elseif($product->getTypeId()=='configurable') {
                $configurableOptions = $this->getConfigurableOptions($product,$domtree,$store);
                $currentprod->appendChild($configurableOptions);
                $price=$product->getFinalPrice();
            } else {        //simple
                $discountedPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                $price=$this->priceHelper->currency($product->getFinalPrice(), true, false);
                if($discountedPrice && $discountedPrice<$product->getFinalPrice()) {
                    $price=$this->priceHelper->currency($discountedPrice, true, false);
                }
                $qty=$this->stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
                $currentprod->appendChild($domtree->createElement('stock', (int)$qty));
                $currentprod->appendChild($domtree->createElement('threshold_stock', $this->stockRegistry->getStockItem($product->getId())->getMinQty()));
            }
            $currentprod->appendChild($domtree->createElement('price', $price));

            foreach ($this->mapping as $nodeName => $attrName) {
                if($nodeName=='description') {
                    $descTag=$domtree->createElement('description');
                    $descTag->appendChild($domtree->createCDATASection($product->getData($attrName)));
                    $currentprod->appendChild($descTag);
                }
                else {
                    if ($product->getData($attrName)) {
                        $value=$product->getResource()->getAttribute($attrName)->getFrontend()->getValue($product);
                        $currentprod->appendChild($domtree->createElement($nodeName, htmlspecialchars($value)));
                    }
                }
            }

            if ($imageLink) {
                $currentprod->appendChild($domtree->createElement('image_link', $imageLink));
            }

            if ($imageSecondaryLink) {
                $currentprod->appendChild($domtree->createElement('image_link2', $imageSecondaryLink));
            }
        }


        $domtree->save($filePath);

    }

    public function mergeText($string)
    {
        $retString=str_replace(',', '|', $string);
        return $retString;
    }

    /** Add image gallery to $product */
    public function addGallery($product)
    {
        $this->galleryReadHandler->execute($product);
    }

    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function updateFlat($productId, $attribute, $value)
    {
        if(!$this->getConfigValue('catalog/frontend/flat_catalog_product')) { //flat is not enabled, no need to update
            return true;
        }
        //get default store

        $storeCode=$this->getConfigValue('cocote/general/store');
        if (!$storeCode) {
            $storeCode='default';
        }

        $stores = $this->storeManager->getStores(true, false);
        foreach($stores as $store){
            if($store->getCode() === $storeCode){
                $storeId = $store->getId();
            }
        }

        $tableName=$this->getFlatTableName($storeId);

        $connection = $this->resource->getConnection();

        $sql='UPDATE '.$tableName.' SET '.$attribute.'="'.$value.'"';

        if($productId) {
            $sql.=' WHERE entity_id='.$productId;
        }
        $connection->query($sql);

    }

    public function getTable($name)
    {
        return $this->resource->getTableName($name);
    }

    /**
     * Retrieve Catalog Product Flat Table name
     *
     * @param int $storeId
     * @return string
     */
    public function getFlatTableName($storeId)
    {
        return sprintf('%s_%s', $this->getTable('catalog_product_flat'), $storeId);
    }

    public function getBestCategory($ids) {
        $ret='';
        if(!sizeof($ids)) {
            return $ret;
        }
        $level=0;
        foreach($ids as $id) {
            $category=$this->getCategory($id);
            if($category['level']>$level) {
                $bestCategory=$category;
                $level=$category['level'];
            }
        }
        $path=explode('/',$bestCategory['path']);
        for($i=2;$i<sizeof($path)-1;$i++) {
            $cat=$this->getCategory($path[$i]);
            $ret.=$this->formatCategoryName($cat['name']).'>';
        }
        $ret.=$this->formatCategoryName($bestCategory['name']);
        return $ret;
    }

    public function formatCategoryName($name) {
        $name=str_replace(' ','-',$name);
        $regexp = '/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);/i';
        $name=html_entity_decode(preg_replace($regexp, '$1', htmlentities($name)));
        return $name;
    }

    public function getCategory($id) {
        if(!sizeof($this->categoriesList)) {

            $storeCode=$this->getConfigValue('cocote/general/store');
            if (!$storeCode) {
                $storeCode='default';
            }
            $this->storeManager->setCurrentStore($storeCode);

            $categories=[];

            $allCategories= $this->categoryFactory->create()
                ->addAttributeToSelect('*')
                ->setStore($this->storeManager->getStore());

            foreach($allCategories as $cat) {
                $categories[$cat->getId()]['name'] = $cat->getName();
                $categories[$cat->getId()]['level'] = $cat->getLevel();
                $categories[$cat->getId()]['path'] = $cat->getPath();
            }
            $this->categoriesList=$categories;
        }
        return $this->categoriesList[$id];
    }

    public function getModuleVersion() {
        return $this->moduleList->getOne('Cocote_Feed')['setup_version'];
    }
    
    
    public function getConfigurableOptions($product,$domtree,$store) {
        $attributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        
        $optionsList=$domtree->createElement('variations');

        $attributesMapping=[];

        foreach ($attributes as $attribute) {
            $attributesMapping[$attribute['attribute_code']]=$attribute['store_label'];
        }

        $childProducts = $product->getTypeInstance(true)->getUsedProducts($product);
        foreach($childProducts as $simpleProd) {

            $idArray=[];
            foreach($attributesMapping as $code=>$id) {
                $idArray[]=$id.' - '.$simpleProd->getResource()->getAttribute($code)->getFrontend()->getValue($simpleProd);
            }
            $qty=$this->stockState->getStockQty($simpleProd->getId(), $simpleProd->getStore()->getWebsiteId());
            $minQty=$this->stockRegistry->getStockItem($product->getId())->getMinQty();

            $variation=$domtree->createElement('variation');

            $variation->appendChild($domtree->createElement('variation_id',$simpleProd->getId()));
            $variation->appendChild($domtree->createElement('variation_name',$simpleProd->getName()));
            $variation->appendChild($domtree->createElement('variation_reference',$simpleProd->getSku()));
            $variation->appendChild($domtree->createElement('variation_stock',$qty));
            if($simpleProd->getWeight()) {
                $variation->appendChild($domtree->createElement('variation_weight',$simpleProd->getWeight()));
            }
            $variation->appendChild($domtree->createElement('variation_threshold_stock',$minQty));
            $variation->appendChild($domtree->createElement('variation_price',$simpleProd->getFinalPrice()));
            $variation->appendChild($domtree->createElement('variation_options',implode(',',$idArray)));


            $descTag=$domtree->createElement('variation_short_description');
            $description=$simpleProd->getResource()->getAttributeRawValue($simpleProd->getId(),'short_description',$this->storeManager->getStore()->getId());
            if(!is_array($description)) {
                $descTag->appendChild($domtree->createCDATASection($description));
            }
            $variation->appendChild($descTag);


            $descTag=$domtree->createElement('variation_description');
            $description=$simpleProd->getResource()->getAttributeRawValue($simpleProd->getId(),'description',$this->storeManager->getStore()->getId());
            if(!is_array($description)) {
                $descTag->appendChild($domtree->createCDATASection($description));
            }
            $variation->appendChild($descTag);

            if($simpleProd->getImage()) {
                $image=$store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' .$simpleProd->getImage();
                $variation->appendChild($domtree->createElement('variation_image',$image));
            }
            $optionsList->appendChild($variation);
        }
        return $optionsList;
    }

    public function updatePriceStock() {
        $collection = $this->cocoteProductFactory->create()->getCollection();
        foreach($collection as $item){
            $item->delete();
        }

        //send
    }

    public function saveProductToUpdate($productId) {
        $productUpdate = $this->cocoteDataProductFactory->create();
        $productUpdate->setData('product_id',$productId);
        $this->cocoteProductRepository->save($productUpdate);
    }

    public function getMagentoVersion() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();
        return $version;
    }

    public function saveToken($token,$orderId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $query="insert into cocote_token (order_id,token) values ('".$orderId."','".$token."')";
        $connection->query($query);
        setcookie('Cocote-token', null, -1, '/');
    }

    public function getToken($orderId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $query = 'SELECT token FROM cocote_token WHERE order_id = ' . (int)$orderId . ' LIMIT 1';
        $token = $connection->fetchOne($query);
        return $token;
    }
}
