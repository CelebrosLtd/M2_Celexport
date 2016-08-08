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

use Magento\Framework\Stdlib\Datetime;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_ENABLED = 'celexport/export_settings/export_enabled';
    const CONFIG_EXPORT_PATH = 'celexport/export_settings/path';
    const CONFIG_EXPORT_LIFETIME = 'celexport/export_settings/export_lifetime';
    const CONFIG_EXPORT_ORDERS = 'celexport/export_settings/export_data_history';
    const CONFIG_EXPORT_ORDERS_FILENAME = 'celexport/export_settings/datahistoryname';
    const CONFIG_EXPORT_CHUNK_SIZE = 'celexport/advanced/export_chunk_size';
    const CONFIG_EXPORT_PROCESS_LIMIT = 'celexport/advanced/export_process_limit';
    const CONFIG_CRON_LOG_LIFETIME = 'celexport/advanced/cronlog_lifetime';
    protected $_urlBuilder;
    public $_cssMin;
    public $_jsMin;
    protected $_stores;
    protected $_dir;
    protected $_assetRepo;
    protected $_resource;
    protected $dirWrite;
    
    /**
     * @param \Magento\Framework\App\Helper\Context $context,
     * @param \Magento\Framework\Code\Minifier\Adapter\Css\CSSmin $cssMin,
     * @param \Magento\Framework\Code\Minifier\Adapter\Js\JShrink $jsMin,
     * @param \Magento\Store\Model\StoreManager $stores,
     * @param \Magento\Framework\View\Asset\Repository $assetRepo,
     * @param \Magento\Framework\Filesystem\DirectoryList $dir,
     * @param \Magento\Framework\Filesystem $filesystem,
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Code\Minifier\Adapter\Css\CSSmin $cssMin,
        \Magento\Framework\Code\Minifier\Adapter\Js\JShrink $jsMin,
        \Magento\Store\Model\StoreManager $stores,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_cssMin = $cssMin;
        $this->_jsMin = $jsMin;
        $this->_stores = $stores;
        $this->_dir = $dir;
        $this->_assetRepo = $assetRepo;
        $this->_resource = $resource;
        $this->_log = $filesystem->getDirectoryWrite($dir::ROOT);
        parent::__construct($context);
    }
    
    public function isEnabled($store = null)
    {
        if (!$store) {
            $store = $this->getCurrentStore();
        }
        
        return (bool)$this->scopeConfig->getValue(self::CONFIG_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
    }
    
    public function isOrdersExport($store = null)
    {
        if ($store) {
            $store = $this->getCurrentStore();
        }
        
        return (bool)$this->scopeConfig->getValue(self::CONFIG_EXPORT_ORDERS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
    }
    
    public function getDataHistoryFileName($store = null)
    {
        if ($store) {
            $store = $this->getCurrentStore();
        }
        
        return $this->scopeConfig->getValue(self::CONFIG_EXPORT_ORDERS_FILENAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
    }
    
    public function getCurrentEnvStamp()
    {
        return base64_encode($this->_urlBuilder->getBaseUrl(array('_type' => \Magento\Framework\UrlInterface::URL_TYPE_WEB)));
    }
    
    public function getExportLifetime()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_EXPORT_LIFETIME);
    }
    
    public function getAllStores()
    {
        return $this->_stores->getStores();
    }
    
    public function setCurrentStore($storeId)
    {
        try {
            $this->_stores->setCurrentStore($storeId);
        } catch (\Exception $e) {
        }
    }
    
    public function getCurrentStore()
    {
        return $this->_stores->getStore();
    }
    
    public function getConfig($path, $store = null)
    {
        if ($store) {
            $store = $this->getCurrentStore();
        }
        
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
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
    
    public function logProfiler($msg, $process = null)
    {
        if (!$this->scopeConfig->getValue('celexport/advanced/enable_log')) {
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
                echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html><head><style type="text/css">
                ul { list-style-type:none; padding:0; margin:0; }
                li { margin-left:0; border:1px solid #ccc; margin:2px; padding:2px 2px 2px 2px; font:normal 12px sans-serif;  }
                img { margin-right:5px; }
                </style><title>Conversion Pro Exporter</title></head>
                <body><ul>';
                break;
            case 'icon':
                echo '<li style="background-color: rgb(128, 128, 128); color:rgb(255,255,255);">
                <img style="margin-right: 5px;" src="' . $this->getIconUrl('note_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'info':
                echo '<li><img style="margin-right: 5px;" src="' . $this->getIconUrl('note_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'warning':
                echo '<li style="background-color: rgb(255, 255, 128);"><img style="margin-right: 5px;" src="' . $this->getIconUrl('fam_bullet_error.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'success':
                echo '<li style="background-color: rgb(128, 255, 128);"><img src="' . $this->getIconUrl('fam_bullet_success.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'section':
                echo '<li style="background-color: rgb(100, 149, 237);"><img src="' . $this->getIconUrl('fam_bullet_success.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            case 'error':
                echo '<li style="background-color: rgb(255, 187, 187);"><img src="' . $this->getIconUrl('error_msg_icon.gif') . '" alt=' . $alt . '/>' . $text . '</li>';
                break;
            default:
                echo '</ul></body></html>';
        }
    }
    
    public function getIconUrl($file)
    {
        return $this->_assetRepo->getUrl('Celebros_Celexport::images/' . $file);
    }
    
    public function getCronlogLifetime()
    {
        return (int)$this->scopeConfig->getValue(self::CONFIG_CRON_LOG_LIFETIME);
    }
}
