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

class Cron extends \Magento\Backend\Block\Widget\Grid\Extended
{
    const DEFAULT_FILTER_OFFSET = 1;
    
    protected $_collection;
    protected $_timezone;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Celebros\Celexport\Model\ResourceModel\Cronlog\Collection $collection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->_collection = $collection;
        $this->_timezone = $timezone;
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
        $borderTime = date('Y-m-d H:i:s', ($this->_timezone->scopeTimeStamp() - self::DEFAULT_FILTER_OFFSET * 3600));
        $adminTimeZone = new \DateTimeZone(
            $this->_scopeConfig->getValue(
                $this->_localeDate->getDefaultTimezonePath(),
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
        $borderTime = new \DateTime($borderTime, $adminTimeZone);
        $this->setDefaultFilter(['executed-at' => ['from' => $this->_localeDate->formatDateTime($borderTime), 'locale' => 'en_US']]);
        $this->setUseAjax(TRUE);
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
        $this->addColumn('executed-at', [
            'header'           => __('Executed At'),
            'index'            => 'executed_at',
            'type'             => 'datetime',
            'filter_time'      => TRUE
        ]);
        
        $this->addColumn('event', [
            'header' => __('Event'),
            'index'  => 'event'
        ]);
        
        return parent::_prepareColumns();
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/crongrid', ['_current' => TRUE]);
    }
    
    public function getRowUrl($row)
    {
        return FALSE;
    }
    
}