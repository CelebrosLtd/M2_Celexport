<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Celexport\Observer;

use Magento\Framework\Event\ObserverInterface;
use Celebros\Celexport\Helper\Data as Helper;
use Celebros\Celexport\Helper\Cron as Scheduler;
use Magento\Framework\Message\ManagerInterface;

class ImagesCacheAfter implements ObserverInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param Helper $helper
     * @param Scheduler $scheduler
     * @param ManagerInterface $message
     * @return void
     */
    public function __construct(
        Helper $helper,
        Scheduler $scheduler,
        ManagerInterface $message
    ) {
        $this->helper = $helper;
        $this->scheduler = $scheduler;
        $this->messageManager = $message;
    }

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helper->isAutoscheduleImages()) {
            $timescheduled = $this->scheduler->scheduleNewExport();
            if (is_array($timescheduled)) {
                $timescheduled = $this->scheduler->timeToString($timescheduled);
                $message = __("Celebros export cron job is scheduled at $timescheduled <br/>");
            } else {
                $message = __("Celebros export cron job are already exist <br/>");
            }

            $this->messageManager->addNotice($message);
        }
    }
}
