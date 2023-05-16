<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Block\Adminhtml\Debug\Cron;

use Magento\Store\Model\Store;

class ExportLogs extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var Magento\Framework\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var \Celebros\Celexport\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Celebros\Celexport\Helper\Data $helper
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Celebros\Celexport\Helper\Data $helper,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->directoryList = $directoryList;
        $this->helper = $helper;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function getLogFilesList()
    {
        $dir = $this->helper->getExportPath();
        $files = scandir($dir);
        $result = [];
        foreach ($files as $key => $file) {
            if (strpos((string) $file, ".log") !== false) {
                $fl = explode(".", (string) $file);
                if (isset($fl[0])) {
                    $date = $this->_localeDate->date((int)$fl[0])->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
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
        $files = $this->getLogFilesList();
        $collection = $this->collectionFactory->create();
        foreach ($files as $file) {
            $varienObject = new \Magento\Framework\DataObject();
            $varienObject->setData($file);
            $collection->addItem($varienObject);
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
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
