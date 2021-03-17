<?php

/**
 * Celebros Qwiser - Magento Extension
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 * @category    Celebros
 * @package     Celebros_Celexport
 */

namespace Celebros\Celexport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Cron\Model\Config;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;

class Cron extends AbstractHelper
{
    public const CRON_GROUP = 'default';
    public const CRON_JOB = 'celebros_export';
    public const SCHEDULE_EVERY_MINUTES = 30;

    public function __construct(
        Context $context,
        Config $cronConfig,
        TimezoneInterface $timeZone,
        Schedule $cronSchedule,
        ScheduleFactory $scheduleFactory
    ) {
        $this->_cronConfig = $cronConfig;
        $this->_timeZone = $timeZone;
        $this->_cronSchedule = $cronSchedule;
        $this->_scheduleFactory = $scheduleFactory;
        parent::__construct($context);
    }

    /**
     * Schedule new export process
     *
     * @return int $status
     */
    public function scheduleNewExport()
    {
        //Flooring the minutes
        $startTimeSeconds = ((int)($this->_timeZone->scopeTimeStamp() / 60)) * 60;
        //Ceiling to the next 5 minutes
        $startTimeMinutes = $startTimeSeconds / 60;
        $startTimeMinutes = ((int)($startTimeMinutes / 5)) * 5 + 5;
        $startTimeSeconds = $startTimeMinutes * 60;

        $jobs = $this->_cronConfig->getJobs();

        if (isset($jobs[self::CRON_GROUP])) {
            $i = 0;
            foreach ($jobs[self::CRON_GROUP] as $jobCode => $jobConfig) {
                if (strpos($jobCode, self::CRON_JOB) === false) {
                    continue;
                }

                $timeCreated = strftime(
                    '%Y-%m-%d %H:%M:%S',
                    $this->_timeZone->scopeTimeStamp()
                );
                $timeScheduled = strftime(
                    '%Y-%m-%d %H:%M:%S',
                    $startTimeSeconds + $i * 60 * self::SCHEDULE_EVERY_MINUTES
                );
                try {
                    $lastItem = $this->_cronSchedule->getCollection()
                        ->addFieldToFilter('job_code', self::CRON_JOB)
                        ->addFieldToFilter('scheduled_at', $timeScheduled)
                        ->getLastItem();
                    if (!$lastItem->getScheduleId()) {
                        $newItem = $this->_scheduleFactory->create();
                        $newItem->setJobCode($jobCode)
                            ->setCreatedAt($timeCreated)
                            ->setScheduledAt($timeScheduled)
                            ->setStatus('pending')
                            ->save();

                        return $timeScheduled;
                    } else {
                        return false;
                    }
                } catch (\Exception $e) {
                    throw new \Exception(__('Unable to schedule Cron'));
                }

                $i++;
            }
        }

        return false;
    }
}
