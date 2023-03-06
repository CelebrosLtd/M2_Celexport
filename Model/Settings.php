<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Celexport\Model;

use Celebros\Celexport\Helper\Data as Helper;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;

class Settings implements \Celebros\Celexport\Api\SettingsInterface
{
    /**
     * @param \Celebros\Celexport\Helper\Data $celebrosHelper
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @return void
     */
    public function __construct(
        Helper $celebrosHelper,
        CacheTypeList $cacheTypeList
    ) {
        $this->helper = $celebrosHelper;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function getSettings(int $storeId = null)
    {
        $settings = $this->helper->getAllSettings($storeId);
        $stores = [];
        foreach ($this->helper->getAllStores() as $store) {
            $stores[] = $store->getData();
        }

        return ['response' => [
            'store_id' => $storeId,
            'settings' => $settings,
            'stores' => $stores
        ]];
    }

    public function setSettings(array $settingsData, int $storeId = null)
    {
        foreach ($settingsData as $name => $value) {
            $return[$name] = $this->helper->setConfig($name, $value, $storeId);
            $this->cacheTypeList->cleanType(
                CacheTypeConfig::TYPE_IDENTIFIER
            );
        }

        $settings = $this->helper->getAllSettings($storeId);

        return ['response' => [
            'settings' => $settings,
            'store_id' => $storeId
        ]];
    }
}
