<?php

namespace Cocote\Feed\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Generatebutton extends Field
{
    protected $backendUrl;

    public function __construct(
        \Magento\Backend\Model\UrlInterface $backendUrl,
        Context $context,
        array $data = []
    ) {
        $this->backendUrl = $backendUrl;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $url = $this->backendUrl->getUrl('cocote/cocote/generate');

        $html =$this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => $element->getHtmlId(),
                'label' => __('Generate Now !'),
            ]
        )
        ->setOnClick("setLocation('$url')")
        ->toHtml();

        $html .= '<h1 style="display:none" id="cocote_generate_generate_message">' . __(
            'Please save configuration first'
        ) . '</h1>';

        return $html;
    }
}
