<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Celexport\Api\Data;

interface ExportManagementInterface
{
    /**
     * Stores List
     *
     * @return string[]|null
     */
    public function getStores();

    /**
     * Status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * System PID
     *
     * @return int|null
     */
    public function getPid();

    /**
     * Export process ID
     *
     * @return int|null
     */
    public function getExportProcessId();
}
