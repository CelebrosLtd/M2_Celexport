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
     * @var int
     */
    protected $_chunkId;

    /**
     * @var int
     */
    protected $_storeId;

    /**
     * @var int
     */
    protected $_processId;

    /**
     * @var Store
     */
    protected $_store;

    /**
     * @var Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

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
     * @var Export
     */
    protected $_helper;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Export $helper
     * @param Store $store
     * @param Cache $cache
     * @param State $state
     * @return void
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Export $helper,
        Store $store,
        Cache $cache,
        State $state,
        Json $json
    ) {
        $this->_objectManager = $objectManager;
        $this->_helper = $helper;
        $this->_store = $store;
        $this->_cache = $cache;
        $this->_state = $state;
        $this->_json = $json;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('celebros:process')
            ->addArgument('chunk_id')
            ->addArgument('store_id')
            ->addArgument('process_id');
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->_state->setAreaCode('frontend');
        $this->_helper->initExportProcessSettings();
        $this->_chunkId = $input->getArgument('chunk_id');
        $this->_storeId = $input->getArgument('store_id');
        $this->_processId = $input->getArgument('process_id');
        $this->_store = $this->_store->load($this->_storeId);
        $process_error = 'no_errors';
        try {
            $_fPath = $this->_helper->getExportPath((int)$this->_processId)
                . '/' . $this->_store->getWebsite()->getCode()
                . '/' . $this->_store->getCode();

            if (!is_dir($_fPath)) {
                $dir = mkdir($_fPath, 0777, true);
            }

            $filePath = $_fPath . '/' . 'export_chunk_' . $this->_chunkId . "." . 'txt';

            $fh = fopen($filePath, 'ab');
            if (!$fh) {
                $this->_helper->logProfiler('Cannot create file from separate process.', '');
                return;
            }

            $ids = $this->_json->unserialize(
                $this->_cache->load('export_chunk_' . $this->_processId . '_' . $this->_chunkId)
            );
            $customAttributes = $this->_cache->load('export_custom_fields_' . $this->_processId);

            $str = $this->_helper->getProductsData($ids, $customAttributes, $this->_storeId, $this->_objectManager);
            fwrite($fh, $str);
            fclose($fh);

            $this->_cache->save(
                $process_error,
                'process_' . $this->_processId . '_' . $this->_chunkId,
                [],
                Exporter::CACHE_LIFETIME
            );

            $output->writeln(0);
        } catch (\Exception $e) {
            $this->_helper->logProfiler('Caught exception: ' . $e->getMessage(), $this->_chunkId);
            $output->writeln(1);
        }
    }
}
