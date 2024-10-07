<?php
/**
 * Celebros (C) 2024. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Observer;

use Celebros\Celexport\Model\ExportManagement;

class CatalogUpdate
{
    /**
     * @var ExportManagement
     */
    private ExportManagement $exportManagement;

    /**
     * @param ExportManagement $exportManagement
     */
    public function __construct(
        ExportManagement $exportManagement
    ) {
        $this->exportManagement = $exportManagement;
    }

    /**
     * Run export by cron
     */
    public function execute()
    {
        $this->exportManagement->startExportProcess();
    }
}
