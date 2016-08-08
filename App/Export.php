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
namespace Celebros\Celexport\App;

use Magento\Framework\App\Bootstrap;

class Export implements \Magento\Framework\AppInterface
{
    protected $_chunkId;
    protected $_storeId;
    protected $_processId;
    protected $_store;
    protected $_objectManager;
    protected $_state;
    public $helper;
    
    /**
     * @param string $chunkId
     * @param string $storeId
     * @param string $processId
     * @param \Magento\Framework\App\Console\Response $response
     */
    public function __construct(
        $chunkId,
        $storeId,
        $processId,
        \Celebros\Celexport\Helper\Export $helper,
        \Magento\Store\Model\Store $store,
        \Celebros\Celexport\Model\Cache $cache,
        \Magento\Framework\App\State $state,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_chunkId = $chunkId;
        $this->_storeId = $storeId;
        $this->_store = $store->load($this->_storeId);
        $this->_cache = $cache;
        $this->_processId = $processId;
        $this->_state = $state;
        $this->_objectManager = $objectManager;
        $this->helper = $helper;
    }
    
    public function launch()
    {
        $this->_state->setAreaCode('frontend');
        ini_set('memory_limit', $this->helper->getMemoryLimit() . 'M');
        set_time_limit(18000);
        ini_set('max_execution_time', 18000);
        ini_set('display_errors', 1);
        ini_set('output_buffering', 0);
        
        $bExportProductLink = true;
        $process_error = 'no_errors';
        
        try {
            $_fPath = $this->helper->getExportPath((int)$this->_processId) . '/' . $this->_store->getWebsite()->getCode() . '/' . $this->_store->getCode();
           
            if (!is_dir($_fPath)) {
                $dir = @mkdir($_fPath, 0777, true);
            }
            
            $filePath = $_fPath . '/' . 'export_chunk_' . $this->_chunkId . "." . 'txt';
            
            $fh = fopen($filePath, 'ab');
            if (!$fh) {
                $this->helper->logProfiler('Cannot create file from separate process.', (int)$_SERVER['argv'][4]);
                exit;
            }
            
            $item = $this->_cache->getCollection()->addFieldToFilter('name', 'export_chunk_' . $this->_processId . '_' . $this->_chunkId)->getLastItem();
            $rows = json_decode($item->getContent());
            
            $item->delete();
            $hasData = count($rows);
            
            $str = null;
            $ids = array();
            foreach ($rows as $row) {
                $ids[] = $row->entity_id;
            }
            
            //Prepare custom attributes list.
            $customAttributes = json_decode($this->_cache->getCollection()
                ->addFieldToFilter('name', 'export_custom_fields_' . $this->_processId)
                ->getLastItem()
                ->getContent());
                
            $str = $this->helper->getProductsData($ids, $customAttributes, $this->_storeId, $this->_objectManager);
            fwrite($fh, $str);
            fclose($fh);
        } catch (\Exception $e) {
            $this->helper->logProfiler('Caught exception: ' . $e->getMessage(), $this->_chunkId);
        }
        
        $this->_cache->setName('process_' . $this->_processId . '_' . $this->_chunkId)
            ->setContent($process_error)
            ->save();
        die;
    }
    
    public function catchException(Bootstrap $bootstrap, \Exception $exception)
    {
        return false;
    }
}
