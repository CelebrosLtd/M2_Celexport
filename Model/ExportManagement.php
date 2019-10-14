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
namespace Celebros\Celexport\Model;

class ExportManagement implements \Celebros\Celexport\Api\ExportManagementInterface
{
    protected $_storeId;
    
    public function __construct(
        \Celebros\Celexport\Model\Exporter $celebrosExport,
        \Celebros\Celexport\Helper\Data $celebrosHelper,
        \Magento\Sales\Model\Order\Item $ordeModel,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->celebrosExport = $celebrosExport;
        $this->helper = $celebrosHelper;
        $this->orderModel = $ordeModel;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_dir = $dir;
        $this->transportBuilder = $transportBuilder;
    }
    
    public function getIntMediaFolder($folder)
    {
        return $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath() . $folder;
    }    
    
    public function exportData($dataType, int $storeId)
    {
        $this->_storeId = $storeId;
        $methodName = 'getData_' . $dataType;
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }
        
        return [
            'message' => 'data type is not exist'
        ];
    }
    
    protected function _prepareExportFolder()
    {
        if (!file_exists($this->getIntMediaFolder('celebros'))) {
            mkdir($this->getIntMediaFolder('celebros'), 0777);
        }
        
        array_map('unlink', array_filter((array) glob($this->getIntMediaFolder('celebros') . "/*")));   
    }
    
    protected function _moveAndRenameExportZip($path)
    {
        $pathArr = explode("/", $path);
        $name = $pathArr[count($pathArr) - 4];
        copy($path, $this->getIntMediaFolder('celebros') . "/" . $name . ".zip");
        
        return $this->convertPathToUrl($this->getIntMediaFolder('celebros') . "/" . $name . ".zip");
    }
    
    public function getData_start_export()
    {
        $storeId = $this->_storeId ? : 1;
        $pid = (int)shell_exec('nohup php ' . $this->_dir->getRoot() . '/bin/magento celebros:export ' . $storeId .' > /dev/null & echo $!');
        return [
            'pid' => $pid,
        ];
    }
    
    public function convertPathToUrl($path)
    {
        $rootPath = $this->_dir->getRoot();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        
        return str_replace($this->_dir->getRoot(), $this->storeManager->getStore()->getBaseUrl(), $path);
    }   

    public function getData_is_export_ready()
    {
        $pid = $this->_storeId;
        if (is_array(glob($this->getIntMediaFolder('celebros') . "/*.zip"))) {
            foreach (glob($this->getIntMediaFolder('celebros') . "/*.zip") as $filename) {
                return [
                    'result' => 'success',
                    'file' => $filename
                ];
            }
        }
        
        if ($this->is_process_running($pid)) {
            return [
                'result' => 'in_progress'
            ];
        }
      
        return [
            'result' => 'failed'
        ];
    }
    
    public function startExportProcess($storeId = null)
    {
        try {
            $this->_prepareExportFolder();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->celebrosExport->export_celebros($objectManager, false, $storeId);
            $url = $this->celebrosExport->zipFileName ? : '';
            if ($url) {
                return ['export_url' => $this->_moveAndRenameExportZip($url)];
            } else {
                return [];   
            }
        } catch (\Exception $e) {
            $this->sendNotificationToEmail($e->getMessage());
        }
    }
    
    protected function is_process_running($PID)
    {
        exec("ps $PID", $processState);
        return (count($processState) >= 2);
    }
    
    public function sendNotificationToEmail($message)
    {
        $store = $this->storeManager->getStore()->getId();
        $notificationEmail = $this->helper->getNotificationsEmail($store);
        $transport = $this->transportBuilder->setTemplateIdentifier('celexport_notification_email_template')
            ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
            ->setTemplateVars(
                [
                    'base_url' => $this->storeManager->getStore()->getBaseUrl(),
                    'store' => $this->storeManager->getStore()->getStoreId(),
                    'store_code' => $this->storeManager->getStore()->getCode(),
                    'message' => $message
                ]
            )
            ->setFrom('general')
            ->addTo($notificationEmail)
            ->getTransport();
        $transport->sendMessage();
        
        return $this;
        
    }
}