<?php

namespace Cocote\Feed\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Linkfeed extends Field
{
    protected $helper;

    public function __construct(
        \Cocote\Feed\Helper\Data $helper,
        Context $context,
        array $data = []
    ) {
        $this->helper=$helper;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $url = $this->helper->getFileLink();

        stream_context_set_default(['http' => ['method' => 'HEAD']]);

        if ($this->remote_file_exists($url)) {
            $html='<a target="_blank" href="'.$url.'">'.$url.'</a>';
        } else {
            $html=$url;
        }
        return $html;
    }
    
    function remote_file_exists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200) {
            return true;
        }
    }
}
