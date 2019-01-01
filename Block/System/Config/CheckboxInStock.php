<?php

namespace Cocote\Feed\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CheckboxInStock extends Field
{

    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Context $context,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;

        parent::__construct($context, $data);
    }


    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $checked='';
        if ($this->scopeConfig->getValue('cocote/generate/in_stock_only', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $checked=" checked='checked' ";
        }

        $html = '
       <input type="hidden" name="groups[generate][fields][in_stock_only][value]" value="0">
        <input id="cocote_generate_in_stock_only" name="groups[generate][fields][in_stock_only][value]"
        data-ui-id="checkbox-groups-generate-fields-in_stock_only-value" value="1" '.$checked.' class="" type="checkbox" />
        ';
        return $html;
    }
}
