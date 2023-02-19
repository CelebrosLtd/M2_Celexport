<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Celexport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Cron\Model\Config;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
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
        ScheduleCollectionFactory $scheduleCollectionFactory,
        ScheduleFactory $scheduleFactory
    ) {
        $this->cronConfig = $cronConfig;
        $this->timeZone = $timeZone;
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
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

                $timeScheduledTS = $sts + $i * 60 * self::SCHEDULE_EVERY_MINUTES;
                $timeScheduledLocal = $dateTime->setTimeStamp($timeScheduledTS)->format('Y-m-d H:i:s');
                $timeScheduledUTC = $dateTime->setTimeZone(
                    new \DateTimeZone($this->timeZone->getDefaultTimezone())
                )->format('Y-m-d H:i:s');

                try {
                    $scheduleCollection = $this->scheduleCollectionFactory->create();
                    $lastItem = $scheduleCollection->addFieldToFilter('job_code', self::CRON_JOB)
                        ->addFieldToFilter('scheduled_at', $timeScheduledUTC)
                        ->getLastItem();

                    if (!$lastItem->getScheduleId()) {
                        $newItem = $this->scheduleFactory->create();
                        $newItem->setJobCode($jobCode)
                            ->setScheduledAt($timeScheduledUTC)
                            ->setStatus('pending')
                            ->save();

                        return [
                            $timeScheduledLocal,
                            '(' . $timeScheduledUTC . ' ' . $this->timeZone->getDefaultTimezone() . ')'
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
     * @param array $time
     * @param string $string
     * @return string
     */
    public function timeToString(
        array $time,
        string $string = ''
    ): string {
        return implode(" ", $time);
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
