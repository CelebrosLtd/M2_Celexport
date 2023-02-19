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

interface SettingsGetInterface
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

    /**
     * List of stores
     *
     * @return string|null
     */
    public function getStores();
}
