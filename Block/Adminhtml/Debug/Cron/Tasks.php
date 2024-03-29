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

class Tasks extends \Magento\Backend\Block\Widget\Grid\Extended
{
    public const DEFAULT_FILTER_OFFSET = 24;

    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('crontasksGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $borderTime = date('Y-m-d H:i:s', ($this->_localeDate->scopeTimeStamp() - self::DEFAULT_FILTER_OFFSET * 3600));
        $adminTimeZone = new \DateTimeZone(
            $this->_scopeConfig->getValue(
                $this->_localeDate->getDefaultTimezonePath(),
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
        $borderTime = new \DateTime($borderTime, $adminTimeZone);
        $this->setDefaultFilter(['created-at' => ['from' => $this->_localeDate->formatDateTime($borderTime), 'locale' => 'en_US']]);
        $this->setUseAjax(true);
        $this->setCelebrosFlag(true);
        $this->setVarNameFilter('tasks_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        if ($this->getCelebrosOnly()) {
            $collection->addFieldToFilter('job_code', 'celebros_export');
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('job-code', [
            'header' => __('Job Code'),
            'index'  => 'job_code'
        ]);

        $status_options = [
            \Magento\Cron\Model\Schedule::STATUS_PENDING => \Magento\Cron\Model\Schedule::STATUS_PENDING,
            \Magento\Cron\Model\Schedule::STATUS_RUNNING => \Magento\Cron\Model\Schedule::STATUS_RUNNING,
            \Magento\Cron\Model\Schedule::STATUS_SUCCESS => \Magento\Cron\Model\Schedule::STATUS_SUCCESS,
            \Magento\Cron\Model\Schedule::STATUS_MISSED => \Magento\Cron\Model\Schedule::STATUS_MISSED,
            \Magento\Cron\Model\Schedule::STATUS_ERROR => \Magento\Cron\Model\Schedule::STATUS_ERROR,
        ];

        $this->addColumn('status', [
            'header'  => __('Status'),
            'index'   => 'status',
            'type'    => 'options',
            'options' => $status_options
        ]);

        $this->addColumn('created-at', [
            'header'      => __('Created At'),
            'index'       => 'created_at',
            'type'        => 'datetime',
            'filter_time' => true
        ]);

        $this->addColumn('scheduled-at', [
            'header'      => __('Scheduled At'),
            'index'       => 'scheduled_at',
            'type'        => 'datetime',
            'filter_time' => true
        ]);

        $this->addColumn('executed-at', [
            'header'           => __('Executed At'),
            'index'            => 'executed_at',
            'type'             => 'datetime',
            'filter_time'      => true
        ]);

        $this->addColumn('finished-at', [
            'header'           => __('Finished At'),
            'index'            => 'finished_at',
            'type'             => 'datetime',
            'filter_time'      => true
        ]);

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/tasks', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    public function getCelebrosFlag()
    {
        return true;
    }

    public function getCelebrosOnly()
    {
        return (bool)$this->_request->getParam('celebros_only');
    }
}
