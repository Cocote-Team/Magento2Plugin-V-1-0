<?php
namespace Cocote\Feed\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Generate extends Command
{
    protected $helper;
    protected $appState;

    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\Helper\Context $context,
        \Cocote\Feed\Helper\Data $helper
    ) {
        $this->appState=$appState;
        $this->helper=$helper;

        return parent::__construct();
    }

    protected function configure()
    {
        $this->setName('cocote:generate');
        $this->setDescription('Generate cocote feeed file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('adminhtml'); // or 'frontend', depending on your needs
        try {
            $this->helper->generateFeed();
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }
    }
}
