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
use Magento\Framework\App\State;
use Magento\Framework\App\Cache;
use Celebros\Celexport\Model\Exporter;
use Magento\Store\Model\Store;
use Celebros\Celexport\Helper\Export;
use Magento\Framework\Serialize\Serializer\Json;

class Process extends Command
{
    /**
     * @var Export
     */
    protected $_helper;

    /**
     * @var Store
     */
    protected $_store;

    /**
     * @var Cache
     */
    protected $_cache;

    /**
     * @var State
     */
    protected $_state;

    /**
     * @var Json
     */
    protected $_json;

    /**
     * @param Export $helper
     * @param Store $store
     * @param Cache $cache
     * @param State $state
     * @param Json $json
     */
    public function __construct(
        Export $helper,
        Store $store,
        Cache $cache,
        State $state,
        Json $json
    ) {
        $this->_helper = $helper;
        $this->_store = $store;
        $this->_cache = $cache;
        $this->_state = $state;
        $this->_json = $json;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('celebros:process')
            ->addArgument('chunk_id')
            ->addArgument('store_id')
            ->addArgument('process_id');
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->_state->setAreaCode('frontend');
        $this->_helper->initExportProcessSettings();
        $chunkId = $input->getArgument('chunk_id');
        $storeId = $input->getArgument('store_id');
        $processId = $input->getArgument('process_id');
        $store = $this->_store->load($storeId);
        $process_error = 'no_errors';
        try {
            $_fPath = $this->_helper->getExportPath((int)$processId)
                . '/' . $store->getWebsite()->getCode()
                . '/' . $store->getCode();

            if (!is_dir($_fPath)) {
                $dir = mkdir($_fPath, 0777, true);
            }

            $filePath = $_fPath . '/' . 'export_chunk_' . $chunkId . "." . 'txt';

            $fh = fopen($filePath, 'ab');
            if (!$fh) {
                throw new \Exception(sprintf(
                    "Can't create file %s",
                    $filePath
                ));
            }

            $ids = $this->_json->unserialize(
                $this->_cache->load('export_chunk_' . $processId . '_' . $chunkId)
            );
            $customAttributes = $this->_cache->load('export_custom_fields_' . $processId);

            $str = $this->_helper->getProductsData($ids, $customAttributes, $storeId);
            fwrite($fh, (string) $str);
            fclose($fh);

            $this->_cache->save(
                $process_error,
                'process_' . $processId . '_' . $chunkId,
                [],
                Exporter::CACHE_LIFETIME
            );

            $output->writeln(0);
        } catch (\Exception $e) {
            $this->_helper->logProfiler('Caught exception: ' . $e->getMessage(), $processId);
            $this->_cache->save(
                $e->getMessage(),
                'process_' . $processId . '_' . $chunkId,
                [],
                Exporter::CACHE_LIFETIME
            );
            $output->writeln(1);
        }
    }
}
