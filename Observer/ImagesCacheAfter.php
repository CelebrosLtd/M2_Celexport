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
    protected $helper;

    /**
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * @var ManagerInterface
     */
    protected $message;

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
