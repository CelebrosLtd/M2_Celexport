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
use Magento\Framework\App\Filesystem\DirectoryList;
class Observer
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */ 
    public function dispatch($observer)
    {
        $model = $this->_objectManager->create('Celebros\Celexport\Model\Cronlog');
        $model->addNewTask($observer->getEvent()->getName());
    }

    
    public function catalogUpdate($observer)
    {
        $model = $this->_objectManager->create('Celebros\Celexport\Model\Exporter');
        $model->export_celebros($this->_objectManager, FALSE);
        return $this;
    }
    
}