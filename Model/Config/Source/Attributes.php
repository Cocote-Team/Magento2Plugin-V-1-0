<?php

namespace Cocote\Feed\Model\Config\Source;

class Attributes extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected $attributeFactory;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory
    ) {
        $this->attributeFactory=$attributeFactory;
    }

    public function getValues()
    {
        $values=[];
        $values['---']='';

        $attributeInfo = $this->attributeFactory->getCollection()->addFieldToFilter('entity_type_id', 4);

        foreach ($attributeInfo as $items) {
            $values[$items->getFrontendLabel()]=$items->getAttributeCode();
        }

        return $values;
    }

    public function getAllOptions()
    {
        $values=$this->getValues();
        $options=[];

        foreach ($values as $group => $labels) {
            if (is_array($labels)) {
                $data=[];
                foreach ($labels as $labelName => $labelCode) {
                    $data[]=['label'=>$labelName,'value'=>$labelCode];
                }
                $options[]=['label'=>$group,'value'=>$data];
            } else {
                $options[]=['value'=>$labels, 'label'=>$group];
            }
        }
        return $options;
    }

}
