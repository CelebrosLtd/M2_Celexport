<?php

/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 * @category    Celebros
 * @package     Celebros_Celexport
 */

namespace Celebros\Celexport\Controller\Adminhtml\Export;

use Celebros\Celexport\Helper\Cron as Scheduler;
use Magento\Backend\App\Action\Context;

class Schedule extends \Celebros\Celexport\Controller\Adminhtml\Export
{
    /**
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * @param Context $context
     * @param Scheduler $scheduler
     */
    public function __construct(
        Context $context,
        Scheduler $scheduler
    ) {
        parent::__construct($context);
        $this->scheduler = $scheduler;
    }

    public function execute()
    {
        if (!$this->_isAllowed()) {
            $this->messageManager->addError(
                __('Access Denied')
            );
        } else {
            $timescheduled = $this->scheduler->scheduleNewExport();
            if (is_array($timescheduled)) {
                $timescheduled = $this->scheduler->timeToString($timescheduled);
                $this->messageManager->addSuccess(
                    __("Celebros export cron job is scheduled at $timescheduled <br/>")
                );
            } else {
                $this->messageManager->addWarning(
                    __("Celebros export cron job are already exist <br/>")
                );
            }
        }

        $this->_redirect(
            $this->_redirect->getRefererUrl()
        );
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
