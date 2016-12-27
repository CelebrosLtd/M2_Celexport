<?php
namespace Celebros\Celexport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{
    protected function configure()
    {
        $this->setName('celebros:export:export')
            ->setDescription('Celebros Export Process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        print_r('asdfasdfasdf');
    }
}
