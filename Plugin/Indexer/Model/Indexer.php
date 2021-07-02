<?php

/*
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

namespace Celebros\Celexport\Plugin\Indexer\Model;

use Magento\Indexer\Model\Indexer as ParentIndexer;
use Celebros\Celexport\Helper\Data as Helper;
use Magento\Framework\Message\ManagerInterface;
use Celebros\Celexport\Helper\Cron;

class Indexer
{
    /**
     * @var Helper
     */
    protected $helper;
    
    /**
     * @param Helper $helper
     * @param Cron $cron
     * @param ManagerInterface $message
     * @return void
     */
    public function __construct(
        Helper $helper,
        Cron $cron,
        ManagerInterface $message
    ) {
        $this->helper = $helper;
        $this->cron = $cron;
        $this->messageManager = $message;
    }
    
    /**
     * @param ParentIndexer $subj
     * @param $result
     * @return void
     */
    public function afterReindexAll(
        ParentIndexer $subj,
        $result
    ) {
        $this->scheduleNewExport($subj->getId());
    }
    
    /**
     * @param ParentIndexer $subj
     * @param $result
     * @return void
     */
    public function afterReindexRow(
        ParentIndexer $subj,
        $result
    ) {
        $this->scheduleNewExport($subj->getId());
    }
    
    /**
     * @param ParentIndexer $subj
     * @param $result
     * @return void
     */
    public function afterReindexList(
        ParentIndexer $subj,
        $result
    ) {
        $this->scheduleNewExport($subj->getId());
    }
    
    /**
     * @param string $indexerName
     * @return void
     */
    protected function scheduleNewExport(
        string $indexerName
    ) {
        if ($this->helper->isAutoScheduleEventEnabled($indexerName)) {
            $timescheduled = $this->cron->scheduleNewExport();
            if (!empty($timescheduled)) {
                $timescheduled = $this->cron->timeToString($timescheduled);
                $this->messageManager->addNotice(
                    __("Celebros export cron job is scheduled at $timescheduled <br/>")
                );
            }
        }
    }
}