<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;

class Export extends Command
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var \Celebros\Celexport\Model\ExportManagement
     */
    private $exportManagment;

    /**
     * @param AppState $appState
     * @param \Celebros\Celexport\Model\ExportManagement $exportManagment
     */
    public function __construct(
        AppState $appState,
        \Celebros\Celexport\Model\ExportManagement $exportManagment
    ) {
        $this->appState = $appState;
        $this->exportManagment = $exportManagment;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('celebros:export')
            ->setDescription('Start Celebros export process')
            ->addArgument('store_id')
            ->addArgument('export_process_id');
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getArgument('store_id');
        if ($storeId) {
            $this->appState->setAreaCode('adminhtml');
            $exportProcessId = $input->getArgument('export_process_id') ? : null;
            $response = $this->exportManagment->startExportProcess($storeId, $exportProcessId);
            $output->write("\n");
            if (isset($response['export_url'])) {
                $output->writeln("<info>Export Process Completed</info>");
                $output->writeln("<info>Export File: " . $response['export_url'] . "<info>");
            } else {
                $output->writeln("<info>Export File Was Not Created</info>");
            }
        } else {
            $output->writeln("<info>Store ID not defined<info>");
            return 1;
        }

        return 0;
    }
}
