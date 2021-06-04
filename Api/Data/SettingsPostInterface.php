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

interface SettingsPostInterface
{
    /**
     * Store id
     *
     * @return string|null
     */
    public function getStoreId();
    
    /**
     * Settings
     *
     * @return string|null
     */
    public function getSettings();
}