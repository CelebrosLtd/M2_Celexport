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
 
interface SettingsInterface
{
    /**
     * @param int $storeId
     * @return mixed
     */
    public function getSettings(int $storeId = null);
    
    /**
     * @param array $settingsData
     * @param int $storeId
     * @return mixed
     */
    public function setSettings(array $settingsData, int $storeId = null);
}
