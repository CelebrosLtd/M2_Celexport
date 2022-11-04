<?php

/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Celexport\Api;

interface ExportManagementInterface
{
    /**
     * @param string $dataType
     * @param int $storeId
     * @return \Celebros\Celexport\Api\Data\ExportManagementInterface
     */
    public function exportData($dataType, int $id);
}
