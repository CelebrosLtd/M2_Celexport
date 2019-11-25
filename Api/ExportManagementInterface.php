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
namespace Celebros\Celexport\Api;
 
interface ExportManagementInterface
{
    /**
     * @param string $dataType
     * @param int $storeId
     * @return mixed
     */
    public function exportData($dataType, int $storeId);
}