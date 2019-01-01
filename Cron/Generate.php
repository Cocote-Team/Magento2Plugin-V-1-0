<?php

namespace Cocote\Feed\Cron;

class Generate
{
    protected $helper;

    public function __construct(
        \Magento\Framework\App\State $appState,
        \Cocote\Feed\Helper\Data $helper
    ) {
        $this->helper=$helper;
    }

    public function execute()
    {
        $this->helper->generateFeed();
    }
}
