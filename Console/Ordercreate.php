<?php
namespace Cocote\Feed\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Ordercreate extends Command
{
    protected $helper;
    protected $appState;

    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\Helper\Context $context,
        \Cocote\Feed\Helper\Orders $helper
    ) {
        $this->appState=$appState;
        $this->helper=$helper;

        return parent::__construct();
    }

    protected function configure()
    {
        $this->setName('cocote:orders');
        $this->setDescription('Generate create test orders');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('frontend');
        try {
            $this->helper->createOrder();
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }
    }
}
