<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Helper;

use \Magento\Framework\Stdlib\Datetime;
use \Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const CONFIG_ENABLED = 'celexport/export_settings/export_enabled';
    public const CONFIG_EXPORT_PATH = 'celexport/export_settings/path';
    public const CONFIG_EXPORT_LIFETIME = 'celexport/export_settings/export_lifetime';
    public const CONFIG_EXPORT_ORDERS = 'celexport/export_settings/export_data_history';
    public const CONFIG_EXPORT_ORDERS_FILENAME = 'celexport/export_settings/datahistoryname';
    public const CONFIG_EXPORT_CHUNK_SIZE = 'celexport/advanced/export_chunk_size';
    public const CONFIG_EXPORT_PROCESS_LIMIT = 'celexport/advanced/export_process_limit';
    public const CONFIG_EXPORT_LOG = 'celexport/advanced/enable_log';
    public const CONFIG_EXPORT_INDEXED_PRICES = 'celexport/export_settings/indexed_prices';
    public const CONFIG_EXPORT_USE_CATALOG_PRICE_RULES = 'celexport/export_settings/use_catalog_price_rules';
    //public const CONFIG_EXPORT_AUTOSCHEDULE_IMAGE_REFRESH = 'celexport/export_settings/images_autoschedule_export';
    public const CONFIG_EXPORT_ENABLE_AUTOSCHEDULE_BY_EVENTS = 'celexport/export_settings/enable_autoschedule_by_events';
    public const CONFIG_EXPORT_AUTOSCHEDULE_EVENTS = 'celexport/export_settings/autoschedule_events';
    public const CONFIG_EXPORT_IMAGES_RESOLUTION = 'celexport/image_settings/images_resolution';
    public const CONFIG_EXPORT_IMAGE_TYPES = 'celexport/image_settings/image_types';
    public const CONFIG_EXPORT_CONF_ENV_STAMP = 'celexport/ftp_prod/env_stamp';
    public const CONFIG_CRON_LOG_LIFETIME = 'celexport/advanced/cronlog_lifetime';
    public const CONFIG_CUSTOM_ATTRIBUTES = 'celexport/export_settings/custom_attributes';
    public const CONFIG_UNSECURE_BASE_URL = 'web/unsecure/base_url';
    public const CONFIG_NOTIFICATION_EMAIL = 'celexport/advanced/notifications_email';

    /**
     * @var string|null
     */
    protected $body = null;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directoryWrite;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManager $stores
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManager $stores,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig
    ) {
        parent::__construct($context);
        $this->storeManager = $stores;
        $this->directoryList = $directoryList;
        $this->assetRepo = $assetRepo;
        $this->resource = $resource;
        $this->resourceConfig = $resourceConfig;
        $this->directoryWrite = $filesystem->getDirectoryWrite($directoryList::ROOT);
    }

    /**
     * Extract list of all export settings for store
     *
     * @param int $store
     * @return array
     */
    public function getAllSettings($store = null) : array
    {
        $result = [];
        foreach ($this->getAllConfigPaths() as $name => $path) {
            $result[$name] = $this->getConfig(
                $path,
                $store
            );
        }

        return $result;
    }

    /**
     * Get list of available config paths
     *
     * @return array
     */
    protected function getAllConfigPaths() : array
    {
        $result = [];
        $reflectionClass = new \ReflectionClass($this);
        $configPaths = $reflectionClass->getConstants();
        foreach ($configPaths as $name => $path) {
            if (strpos($name, 'CONFIG_') !== false) {
                $result[$name] = $path;
            }
        }

        return $result;
    }

    public function getAllStores()
    {
        return $this->storeManager->getStores();
    }

    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    public function setCurrentStore($storeId)
    {
        try {
            $this->storeManager->setCurrentStore($storeId);
        } catch (\Exception $e) {
            $this->logProfiler($e->getMessage());
        }
    }

    public function getConfig(string $path, $store = null, $isBool = false)
    {
        if ($store) {
            $store = $this->getCurrentStore();
        }

        if ($isBool) {
            return $this->scopeConfig->isSetFlag(
                $path,
                ScopeInterface::SCOPE_STORES,
                $store
            );
        }

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    public function setConfig(string $name, $value, $store = null)
    {
        if ($path = $this->_validateConfigPath($name)) {
            $store = ($store !== null) ? $store : 0;
            $this->resourceConfig->saveConfig(
                $path,
                $value,
                $store ? ScopeInterface::SCOPE_STORES : \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT,
                $store
            );

            return $this->getConfig(
                $path,
                $store
            );
        }

        return null;
    }

    protected function _validateConfigPath(string $name) : ?string
    {
        $validPaths = $this->getAllConfigPaths();
        if (isset($validPaths[$name])) {
            return $validPaths[$name];
        }
        return null;
    }

    public function isEnabled($store = null)
    {
        if (!$store) {
            $store = $this->getCurrentStore();
        }

        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    public function isOrdersExport($store = null)
    {
        if ($store) {
            $store = $this->getCurrentStore();
        }

        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_EXPORT_ORDERS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    public function getDataHistoryFileName($store = null)
    {
        if ($store) {
            $store = $this->getCurrentStore();
        }

        return $this->scopeConfig->getValue(
            self::CONFIG_EXPORT_ORDERS_FILENAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    public function getCurrentEnvStamp()
    {
        return sha1((string) $this->scopeConfig->getValue(
            self::CONFIG_UNSECURE_BASE_URL,
            'default',
            0
        ));
    }

    public function getConfiguratedEnvStamp()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_EXPORT_CONF_ENV_STAMP,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES
        );
    }

    public function getExportLifetime()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_EXPORT_LIFETIME);
    }

    public function getExportPath($id = null)
    {
        return $this->directoryList->getRoot() . $this->scopeConfig->getValue(self::CONFIG_EXPORT_PATH) . '/' . $id;
    }

    public function getExportProcessId()
    {
        return (new \DateTime())->getTimestamp();
    }

    public function getExportChunkSize()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_EXPORT_CHUNK_SIZE);
    }

    public function getExportProcessLimit()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_EXPORT_PROCESS_LIMIT);
    }

    public function getNotificationsEmail($store = null)
    {
        if ($store === null) {
            $store = $this->getCurrentStore();
        }

        return $this->scopeConfig->getValue(
            self::CONFIG_NOTIFICATION_EMAIL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    public function useIndexedPrices()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_EXPORT_INDEXED_PRICES);
    }

    public function useCatalogPriceRules()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_EXPORT_USE_CATALOG_PRICE_RULES);
    }

    public function getCronlogLifetime()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_CRON_LOG_LIFETIME);
    }

    public function isCustomAttributesEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_CUSTOM_ATTRIBUTES,
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    public function logProfiler($msg, $process = null)
    {
        if (!$this->scopeConfig->getValue(self::CONFIG_EXPORT_LOG)) {
            return;
        }

        $str = date('Y-m-d H:i:s') . ' ' .  $msg . "\r\n";

        $stream = $this->directoryWrite->openFile($this->getLogFilePath($process), 'a');
        $stream->lock();
        $stream->write($str);
        $stream->unlock();
        $stream->close();
    }

    public function getLogFilePath($processId)
    {
        return $this->scopeConfig->getValue(self::CONFIG_EXPORT_PATH) . '/' . $this->getLogFilename($processId);
    }

    public function getLogFilename($processId)
    {
        return $processId . '.log';
    }

    public function comments_style($kind, $text, $alt = null)
    {
        switch ($kind) {
            case 'header':
                $this->body .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html><head><style type="text/css">
                ul { list-style-type:none; padding:0; margin:0; }
                li { margin-left:0; border:1px solid #ccc; margin:2px; padding:2px 2px 2px 2px; font:normal 12px sans-serif;  }
                img { margin-right:5px; }
                </style><title>Conversion Pro Exporter</title></head>
                <body><ul>';
                break;
            case 'icon':
                $alt = $alt ?? 'icon';
                $this->body .= '<li style="background-color: rgb(128, 128, 128); color:rgb(255,255,255);">
                <img style="margin-right: 5px;" src="' . $this->getIconUrl('note_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'info':
                $alt = $alt ?? 'info';
                $this->body .= '<li><img style="margin-right: 5px;" src="' . $this->getIconUrl('note_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'warning':
                $alt = $alt ?? 'warning';
                $this->body .= '<li style="background-color: rgb(255, 255, 128);"><img style="margin-right: 5px;" src="' . $this->getIconUrl('fam_bullet_error.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'success':
                $alt = $alt ?? 'success';
                $this->body .= '<li style="background-color: rgb(128, 255, 128);"><img src="' . $this->getIconUrl('fam_bullet_success.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'section':
                $alt = $alt ?? 'section';
                $this->body .= '<li style="background-color: rgb(100, 149, 237);"><img src="' . $this->getIconUrl('fam_bullet_success.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'error':
                $alt = $alt ?? 'error';
                $this->body .= '<li style="background-color: rgb(255, 187, 187);"><img src="' . $this->getIconUrl('error_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            default:
                $this->body .= '</ul></body></html>';
        }
    }

    public function getBodyForResponse()
    {
        return $this->body;
    }

    public function getIconUrl($file)
    {
        return $this->assetRepo->getUrl('Celebros_Celexport::images/' . $file);
    }

    public function isAutoScheduleEventEnabled(
        string $eventName,
        $store = null
    ): bool {
        $isEnabled = $this->scopeConfig->isSetFlag(
            self::CONFIG_EXPORT_ENABLE_AUTOSCHEDULE_BY_EVENTS,
            ScopeInterface::SCOPE_STORES,
            $store
        );

        $avEvents = explode(
            ",",
            (string)$this->scopeConfig->getValue(
                self::CONFIG_EXPORT_AUTOSCHEDULE_EVENTS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
                $store
            )
        );

        if (
            $isEnabled
            && in_array($eventName, $avEvents)
        ) {
            return true;
        }

        return false;
    }


    public function isAutoscheduleImages($store = null): bool
    {
        return $this->isAutoScheduleEventEnabled(
            \Celebros\Celexport\Model\Config\Source\Events\Cache::IMAGE_FLUSH_EVENT_NAME,
            $store
        );
    }
}
