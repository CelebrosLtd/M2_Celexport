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

class Cron extends \Magento\Backend\Block\Widget\Grid\Extended
{
    public const DEFAULT_FILTER_OFFSET = 1;

    /**
     * @var \Celebros\Celexport\Model\ResourceModel\Cronlog\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Celebros\Celexport\Model\ResourceModel\Cronlog\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Celebros\Celexport\Model\ResourceModel\Cronlog\CollectionFactory $collectionFactory,
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
        $this->setId('cronlogGrid');
        $this->setDefaultSort('executed_at');
        $this->setDefaultDir('DESC');
        $borderTime = date('Y-m-d H:i:s', ($this->_localeDate->scopeTimeStamp() - self::DEFAULT_FILTER_OFFSET * 3600));
        $adminTimeZone = new \DateTimeZone(
            $this->_scopeConfig->getValue(
                $this->_localeDate->getDefaultTimezonePath(),
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
        $borderTime = new \DateTime($borderTime, $adminTimeZone);
        $this->setDefaultFilter(['executed-at' => ['from' => $this->_localeDate->formatDateTime($borderTime), 'locale' => 'en_US']]);
        $this->setUseAjax(true);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('executed-at', [
            'header'           => __('Executed At'),
            'index'            => 'executed_at',
            'type'             => 'datetime',
            'filter_time'      => true
        ]);

        $this->addColumn('event', [
            'header' => __('Event'),
            'index'  => 'event'
        ]);

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/crongrid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }
}
