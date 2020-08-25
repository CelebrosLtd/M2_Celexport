<?php
/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 ******************************************************************************
 * @category    Celebros
 * @package     Celebros_Celexport
 */
namespace Celebros\Celexport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State as AppState;

class Process extends Command
{
    protected function configure()
    {
        $this->setName('celebros:process')
            ->addArgument('chunk_id')
            ->addArgument('store_id')
            ->addArgument('process_id');
    }
    
    public function __construct(
        AppState $appState,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Celebros\Celexport\Helper\Export $helper,
        \Magento\Store\Model\Store $store,
        \Celebros\Celexport\Model\Cache $cache,
        \Magento\Framework\App\State $state
    ) {
        $this->appState = $appState;
        $this->_objectManager = $objectManager;
        $this->helper = $helper;
        $this->_store = $store;        
        $this->_cache = $cache;
        $this->_state = $state;
        parent::__construct();
    }
    
    protected $_chunkId;
    protected $_storeId;
    protected $_processId;
    protected $_store;
    protected $_objectManager;
    protected $_state;
    protected $_response;
    public $helper;
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_state->setAreaCode('frontend');
        $this->helper->initExportProcessSettings();

        $this->_chunkId = $input->getArgument('chunk_id');
        $this->_storeId = $input->getArgument('store_id');
        $this->_processId = $input->getArgument('process_id');
        $this->_store = $this->_store->load($this->_storeId);
        
        $process_error = 'no_errors';
        
        try {
            $_fPath = $this->helper->getExportPath((int)$this->_processId) . '/' . $this->_store->getWebsite()->getCode() . '/' . $this->_store->getCode();
           
            if (!is_dir($_fPath)) {
                $dir = mkdir($_fPath, 0777, true);
            }
            
            $filePath = $_fPath . '/' . 'export_chunk_' . $this->_chunkId . "." . 'txt';
            
            $fh = fopen($filePath, 'ab');
            if (!$fh) {
                $this->helper->logProfiler('Cannot create file from separate process.', '');
                return;
            }
            
            $item = $this->_cache->getCollection()->addFieldToFilter('name', 'export_chunk_' . $this->_processId . '_' . $this->_chunkId)->getLastItem();
            
            $ids = json_decode($item->getContent()); 
            
            //Prepare custom attributes list.
            $customAttributes = json_decode($this->_cache->getCollection()
                ->addFieldToFilter('name', 'export_custom_fields_' . $this->_processId)
                ->getLastItem()
                ->getContent());
                
            $str = $this->helper->getProductsData($ids, $customAttributes, $this->_storeId, $this->_objectManager);
            fwrite($fh, $str);
            fclose($fh);
            
            $this->_cache->setName('process_' . $this->_processId . '_' . $this->_chunkId)
                ->setContent($process_error)
                ->save();
                
            $output->writeln(0);    
        } catch (\Exception $e) {
            $this->helper->logProfiler('Caught exception: ' . $e->getMessage(), $this->_chunkId);
            $output->writeln(1);
        }
    }
}