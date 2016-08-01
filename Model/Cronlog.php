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
namespace Celebros\Celexport\Model;

class Cronlog extends \Magento\Framework\Model\AbstractModel
{
    protected $_timezone;
    public $helper;

    protected function _construct()
    {
        $this->_init('Celebros\Celexport\Model\ResourceModel\Cronlog');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_timezone = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        $this->helper = $objectManager->create('Celebros\Celexport\Helper\Data');
    }

    public function addNewTask($event, $time = NULL)
    {
        if (!$time) {
            $data['executed_at'] = $this->_timezone->scopeTimeStamp();
        } else {
            $data['executed_at'] = $time;
        }
        
        $data['event'] = $event;
        try {
            $this->setData($data)->save();
        } catch (\Exception $e) {
            return FALSE;
        }
        
        $this->cleanUpCollection($this->getCronlogLifetime());
    }

    public function truncate()
    {
        $tableName = $this->getCollection()->getResource()->getMainTable();
        $conn = $this->getCollection()->getConnection();
        $conn->truncateTable($tableName);    
    }

    public function cleanUpCollection($hours)
    {
        $collection = $this->getCollection();
        $borderTime = $this->_timezone->scopeTimeStamp() - $hours * 3600;
        $borderTime = date('Y-m-d H:i:s', $borderTime);
        $collection->addFieldToFilter('executed_at', array('lt' => $borderTime));
        foreach ($collection as $item) {
            $item->delete();
        }
    }

    public function getCronlogLifetime()
    {
        return (int)$this->helper->getCronlogLifetime();
    }

}