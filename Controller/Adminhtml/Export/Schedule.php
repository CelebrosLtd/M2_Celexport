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
namespace Celebros\Celexport\Controller\Adminhtml\Export;

class Schedule extends \Celebros\Celexport\Controller\Adminhtml\Export
{
    const CRON_GROUP = 'default';
    const CRON_JOB = 'celebros_export';
    
    /**
     * @var \Magento\Cron\Model\Config
     */
    protected $_cronConfig;
    
    /**
     * @var \Magento\Cron\Model\Schedule
     */
    protected $_cronSchedule;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Cron\Model\Config $cronConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Cron\Model\Config $cronConfig
    ) {
        parent::__construct($context);
        $this->_cronConfig = $cronConfig;
        $this->_timezone = $this->_objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        $this->_cronSchedule = $this->_objectManager->create('Magento\Cron\Model\Schedule');
    }
    
    public function execute()
    {
        if (!$this->_isAllowed()) {
            echo $this->__('Access Denied');
            return;
        }
        
        $SCHEDULE_EVERY_MINUTES = 30;
        //Flooring the minutes
        $startTimeSeconds = ((int)($this->_timezone->scopeTimeStamp()/60)) * 60;
        //Ceiling to the next 5 minutes
        $startTimeMinutes = $startTimeSeconds / 60;
        $startTimeMinutes = ((int)($startTimeMinutes / 5)) * 5 + 5;
        $startTimeSeconds = $startTimeMinutes * 60;
        
        $jobs = $this->_cronConfig->getJobs();
        
        if (isset($jobs[self::CRON_GROUP])) {
            $i = 0;
            foreach ($jobs[self::CRON_GROUP] as $jobCode => $jobConfig) {
                if (strpos($jobCode, self::CRON_JOB) === FALSE) {
                    continue;
                }
                
                $timecreated   = strftime('%Y-%m-%d %H:%M:%S', $this->_timezone->scopeTimeStamp());
                $timescheduled = strftime('%Y-%m-%d %H:%M:%S', $startTimeSeconds + $i * 60 * $SCHEDULE_EVERY_MINUTES);
                try {
                    $lastItem = $this->_cronSchedule->getCollection()
                        ->addFieldToFilter('job_code', 'celebros_export')                    
                        ->addFieldToFilter('scheduled_at', $timescheduled)
                        ->getLastItem();
                     
                    if (!$lastItem->getScheduleId()) {
                        $this->_cronSchedule->setJobCode($jobCode)
                        ->setCreatedAt($timecreated)
                        ->setScheduledAt($timescheduled)
                        ->setStatus('pending')
                        ->save();
                        
                        echo "{$jobCode} cron job is scheduled at $timescheduled <br/>";
                    } else {
                        echo "{$jobCode} cron job are already exist at $timescheduled <br/>";
                    }
                
                } catch (Exception $e) {
                    throw new Exception(__('Unable to schedule Cron'));
                }
                
                $i++;
            }
        }
    }
    
    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Celebros_Celexport::export_menu_cron_export');
    }
    
}