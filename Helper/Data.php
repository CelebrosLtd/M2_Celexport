<?php
/**
 * Celebros Qwiser - Magento Extension
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 * @category    Celebros
 * @package     Celebros_Celexport
 */
namespace Celebros\Celexport\Helper;

use \Magento\Framework\Stdlib\Datetime;
use \Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_ENABLED = 'celexport/export_settings/export_enabled';
    const CONFIG_EXPORT_PATH = 'celexport/export_settings/path';
    const CONFIG_EXPORT_LIFETIME = 'celexport/export_settings/export_lifetime';
    const CONFIG_EXPORT_ORDERS = 'celexport/export_settings/export_data_history';
    const CONFIG_EXPORT_ORDERS_FILENAME = 'celexport/export_settings/datahistoryname';
    const CONFIG_EXPORT_CHUNK_SIZE = 'celexport/advanced/export_chunk_size';
    const CONFIG_EXPORT_PROCESS_LIMIT = 'celexport/advanced/export_process_limit';
    const CONFIG_EXPORT_LOG = 'celexport/advanced/enable_log';
    const CONFIG_EXPORT_INDEXED_PRICES = 'celexport/export_settings/indexed_prices';
    const CONFIG_EXPORT_USE_CATALOG_PRICE_RULES = 'celexport/export_settings/use_catalog_price_rules';
    const CONFIG_EXPORT_AUTOSCHEDULE_IMAGE_REFRESH = 'celexport/export_settings/images_autoschedule_export';
    const CONFIG_EXPORT_IMAGES_RESOLUTION = 'celexport/image_settings/images_resolution';
    const CONFIG_EXPORT_IMAGE_TYPES = 'celexport/image_settings/image_types';
    const CONFIG_EXPORT_CONF_ENV_STAMP = 'celexport/ftp_prod/env_stamp';
    const CONFIG_CRON_LOG_LIFETIME = 'celexport/advanced/cronlog_lifetime';
    const CONFIG_CUSTOM_ATTRIBUTES = 'celexport/export_settings/custom_attributes';
    const CONFIG_UNSECURE_BASE_URL = 'web/unsecure/base_url';
    const CONFIG_NOTIFICATION_EMAIL = 'celexport/advanced/notifications_email';
    
    protected $_urlBuilder;
    public $_cssMin;
    public $_jsMin;
    protected $_response;
    protected $_stores;
    protected $_dir;
    protected $_assetRepo;
    protected $_resource;
    protected $_configWriter;
    protected $dirWrite;
    protected $_body = null;
    
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Code\Minifier\Adapter\Css\CSSmin $cssMin
     * @param \Magento\Framework\Code\Minifier\Adapter\Js\JShrink $jsMin
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\Store\Model\StoreManager $stores
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Code\Minifier\Adapter\Css\CSSmin $cssMin,
        \Magento\Framework\Code\Minifier\Adapter\Js\JShrink $jsMin,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Store\Model\StoreManager $stores,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig
    ) {
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_cssMin = $cssMin;
        $this->_jsMin = $jsMin;
        $this->_response = $response;
        $this->_stores = $stores;
        $this->_dir = $dir;
        $this->_assetRepo = $assetRepo;
        $this->_resource = $resource;
        $this->resourceConfig = $resourceConfig;
        $this->_log = $filesystem->getDirectoryWrite($dir::ROOT);
        parent::__construct($context);
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
        return $this->_stores->getStores();
    }
    
    public function getCurrentStore()
    {
        return $this->_stores->getStore();
    }
    
    public function setCurrentStore($storeId)
    {
        try {
            $this->_stores->setCurrentStore($storeId);
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
        
        return (bool)$this->scopeConfig->getValue(self::CONFIG_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $store);
    }
    
    public function isOrdersExport($store = null)
    {
        if ($store) {
            $store = $this->getCurrentStore();
        }
        
        return (bool)$this->scopeConfig->getValue(self::CONFIG_EXPORT_ORDERS, \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $store);
    }
    
    public function getDataHistoryFileName($store = null)
    {
        if ($store) {
            $store = $this->getCurrentStore();
        }
        
        return $this->scopeConfig->getValue(self::CONFIG_EXPORT_ORDERS_FILENAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $store);
    }
    
    public function getCurrentEnvStamp()
    {
        return sha1($this->scopeConfig->getValue(self::CONFIG_UNSECURE_BASE_URL, 'default', 0));
    }
    
    public function getConfiguratedEnvStamp()
    {
        return $this->scopeConfig->getValue(self::CONFIG_EXPORT_CONF_ENV_STAMP, \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
    }
    
    public function getExportLifetime()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_EXPORT_LIFETIME);
    }
    
    public function getExportPath($id = null)
    {
        return $this->_dir->getRoot() . $this->scopeConfig->getValue(self::CONFIG_EXPORT_PATH) . '/' . $id;
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
        if ($store) {
            $store = $this->getCurrentStore();
        }
        
        return $this->scopeConfig->getValue(self::CONFIG_NOTIFICATION_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $store);
    }
    
    public function useIndexedPrices()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_EXPORT_INDEXED_PRICES);
    }
    
    public function isAutoscheduleImages($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_EXPORT_AUTOSCHEDULE_IMAGE_REFRESH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        ); 
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
       
        $stream = $this->_log->openFile($this->getLogFilePath($process), 'a');
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
    
    public function comments_style($kind, $text, $alt)
    {
        switch ($kind) {
            case 'header':
                $this->_body .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html><head><style type="text/css">
                ul { list-style-type:none; padding:0; margin:0; }
                li { margin-left:0; border:1px solid #ccc; margin:2px; padding:2px 2px 2px 2px; font:normal 12px sans-serif;  }
                img { margin-right:5px; }
                </style><title>Conversion Pro Exporter</title></head>
                <body><ul>';
                break;
            case 'icon':
                $this->_body .= '<li style="background-color: rgb(128, 128, 128); color:rgb(255,255,255);">
                <img style="margin-right: 5px;" src="' . $this->getIconUrl('note_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'info':
                $this->_body .= '<li><img style="margin-right: 5px;" src="' . $this->getIconUrl('note_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'warning':
                $this->_body .= '<li style="background-color: rgb(255, 255, 128);"><img style="margin-right: 5px;" src="' . $this->getIconUrl('fam_bullet_error.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'success':
                $this->_body .= '<li style="background-color: rgb(128, 255, 128);"><img src="' . $this->getIconUrl('fam_bullet_success.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'section':
                $this->_body .= '<li style="background-color: rgb(100, 149, 237);"><img src="' . $this->getIconUrl('fam_bullet_success.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'error':
                $this->_body .= '<li style="background-color: rgb(255, 187, 187);"><img src="' . $this->getIconUrl('error_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            default:
                $this->_body .= '</ul></body></html>';
        }
    }
    
    public function getBodyForResponse()
    {
        return $this->_body;
    }
    
    public function getIconUrl($file)
    {
        return $this->_assetRepo->getUrl('Celebros_Celexport::images/' . $file);
    }
}
