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