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
            if ($this->scheduler->scheduleNewExport()) {
                $message = __("New Celebros export task has been scheduled");
            } else {
                $message = __("New Celebros export task hasn't been scheduled");
            }
            
            $this->messageManager->addNotice($message);
        }
    }
}
