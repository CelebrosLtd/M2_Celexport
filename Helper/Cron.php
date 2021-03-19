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
use Magento\Framework\Stdlib\DateTime\Timezone\LocalizedDateToUtcConverterInterface;

class Cron extends AbstractHelper
{
    public const CRON_GROUP = 'default';
    public const CRON_JOB = 'celebros_export';
    public const SCHEDULE_EVERY_MINUTES = 30;

    /**
     * @param Context $context
     * @param Config $cronConfig
     * @param TimezoneInterface $timeZone
     * @param Schedule $cronSchedule
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct(
        Context $context,
        Config $cronConfig,
        TimezoneInterface $timeZone,
        Schedule $cronSchedule,
        ScheduleFactory $scheduleFactory
    ) {
        $this->cronConfig = $cronConfig;
        $this->timeZone = $timeZone;
        $this->cronSchedule = $cronSchedule;
        $this->scheduleFactory = $scheduleFactory;
        parent::__construct($context);
    }

    /**
     * Schedule new export process
     *
     * @return array|null
     */
    public function scheduleNewExport(): ?array
    {
        $dateTime = $this->timeZone->date();
        $sts = $this->generateNextTaskTimestamp($dateTime->getTimeStamp());
        $jobs = $this->cronConfig->getJobs();
        if (isset($jobs[self::CRON_GROUP])) {
            $i = 0;
            foreach ($jobs[self::CRON_GROUP] as $jobCode => $jobConfig) {
                if (strpos($jobCode, self::CRON_JOB) === false) {
                    continue;
                }

                $timeScheduled = $sts + $i * 60 * self::SCHEDULE_EVERY_MINUTES;
                try {
                    $lastItem = $this->cronSchedule->getCollection()
                        ->addFieldToFilter('job_code', self::CRON_JOB)
                        ->addFieldToFilter('scheduled_at', $timeScheduled)
                        ->getLastItem();
                    if (!$lastItem->getScheduleId()) {
                        $newItem = $this->scheduleFactory->create();
                        $newItem->setJobCode($jobCode)
                            ->setScheduledAt($timeScheduled)
                            ->setStatus('pending')
                            ->save();

                        return [
                            $dateTime->setTimeStamp($timeScheduled)->format('Y-m-d H:i:s'),
                            '(' . $dateTime->setTimeZone(
                                new \DateTimeZone($this->timeZone->getDefaultTimezone())
                            )->format('Y-m-d H:i:s') . ' ' . $this->timeZone->getDefaultTimezone() . ')'
                        ];
                    } else {
                        return null;
                    }
                } catch (\Exception $e) {
                    throw new \Exception(__('Unable to schedule Cron'));
                }

                $i++;
            }
        }

        return null;
    }

    /**
     * Calculate timestamp for next task
     *
     * @param int $ts
     * @return int
     */
    protected function generateNextTaskTimestamp(
        int $ts
    ): int {
        //Flooring the minutes
        $sts = ((int)($ts / 60)) * 60;
        //Ceiling to the next 5 minutes
        $stm = $sts / 60;
        $stm = ((int)($stm / 5)) * 5 + 5;
        return  $stm * 60;
    }
}
