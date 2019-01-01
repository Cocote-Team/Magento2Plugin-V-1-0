<?php
namespace Cocote\Feed\Model\Config\Source;

class Stores extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected $storeManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager=$storeManager;
    }

    public function getAllOptions()
    {
        $storeManagerDataList = $this->storeManager->getStores();
        $options = [];

        foreach ($storeManagerDataList as $key => $value) {
            $options[] = ['label' => $value['name'].' - '.$value['code'], 'value' => $value['code']];
        }
        return $options;
    }


}
