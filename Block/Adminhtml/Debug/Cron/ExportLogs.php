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
namespace Celebros\Celexport\Block\Adminhtml\Debug\Cron;

use Magento\Store\Model\Store;

class ExportLogs extends \Magento\Backend\Block\Widget\Grid\Extended
{
    
    protected $_collection;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Data\CollectionFactory $collection,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Celebros\Celexport\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->_dir = $dir;
        $this->helper = $helper;
        $this->timezone = $timezone;
        $files = $this->getLogFilesList();
      
        $this->_collection = $collection->create();
        foreach ($files as $file) {
            $varienObject = new \Magento\Framework\DataObject();
            $varienObject->setData($file);
            $this->_collection->addItem($varienObject);
        }
        
        parent::__construct($context, $backendHelper, $data);
    }
    
    public function getLogFilesList()
    {
        $dir = $this->helper->getExportPath();
        $files = scandir($dir);
        $result = [];
        foreach ($files as $key => $file) {
            if (strpos($file, ".log") !== false) {
                $fl = explode(".", $file);
                if (isset($fl[0])) {
                    $date = $this->timezone->date((int)$fl[0])->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
                }
                
                $result[] = [
                    'date' => $date,
                    'filename' => $file
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('celeblogsGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setVarNameFilter('logs_filter');
        $this->setPagerVisibility(false);
    }
    
    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collection;
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('filename', [
            'header' => __('Filename'),
            'index'  => 'filename',
            'sortable' => false,
            'filter' => false
        ]);
        
        $this->addColumn('date', [
            'header'      => __('Date'),
            'index'       => 'date',
            'type'        => 'datetime',
            'filter' => false,
            'sortable' => false
        ]);
        
        return parent::_prepareColumns();
    }
    
    /**
     * @return string
     */
    public function getGridUrl()
    {
        return false;
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/logs', ['_current' => true, 'filename' => $row->getFilename()]);
    }
    
    public function getMainButtonsHtml()
    {
        return '';
    }
}
