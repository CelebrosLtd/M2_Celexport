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
 
namespace Celebros\Celexport\Api;
 
interface SettingsInterface
{
    /**
     * Get Celebros export module settings for selected store
     *
     * @param int $storeId
     * @return \Celebros\Celexport\Api\Data\SettingsGetInterface
     */
    public function getSettings(int $storeId = null);
    
    /**
     * Import Celebros export module settings for selected store
     *
     * @param string[] $settingsData
     * @param int $storeId
     * @return \Celebros\Celexport\Api\Data\SettingsPostInterface
     */
    public function setSettings(array $settingsData, int $storeId = null);
}
