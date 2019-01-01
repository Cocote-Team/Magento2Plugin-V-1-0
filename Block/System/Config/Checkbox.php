<?php

namespace Cocote\Feed\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Checkbox extends Field
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
        if ($this->scopeConfig->getValue('cocote/location/place_online_the_same', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $checked=" checked='checked' ";
        }

        $html = '
       <input type="hidden" name="groups[location][fields][place_online_the_same][value]" value="0">
        <input id="cocote_location_place_online_the_same" name="groups[location][fields][place_online_the_same][value]"
        data-ui-id="checkbox-groups-location-fields-place-online-the-same-value" value="1" '.$checked.' class="" type="checkbox" />
        ';
        return $html;
    }
}
