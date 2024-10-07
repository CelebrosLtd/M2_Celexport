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

use Magento\Framework\App\Area;
use Magento\Store\Model\ScopeInterface;
use Symfony\Component\Process\Process;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportManagement implements \Celebros\Celexport\Api\ExportManagementInterface
{
    public const NOT_EXIST = "not_exist";
    public const STARTED = "started";
    public const IN_PROGRESS = "in_progress";
    public const DONE = "done";
    public const FAILED = "failed";
    public const ERROR = "error";
    public const MAX_PROCESS_AGE = 7200; /* 2h */
    public const MAX_LAST_UPDATE_AGE = 3600; /* 1h */

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var \Celebros\Celexport\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    private $dir;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @param \Celebros\Celexport\Model\Exporter $exporter
     * @param \Celebros\Celexport\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @return void
     */
    public function __construct(
        \Celebros\Celexport\Model\Exporter $exporter,
        \Celebros\Celexport\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->exporter = $exporter;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->dir = $dir;
        $this->timezone = $timezone;
        $this->transportBuilder = $transportBuilder;
    }

    public function getIntMediaFolder($folder)
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . $folder;
    }

    public function exportData($dataType, int $id)
    {
        $this->id = $id;
        $methodName = 'getData' . str_replace("_", "", ucwords((string) $dataType, "_"));
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        return ['response' => [
            'status' => self::ERROR,
            'message' => 'data type is not exist'
        ]];
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
        $pathArr = explode("/", (string) $path);
        $name = $pathArr[count($pathArr) - 4];
        copy($path, $this->getIntMediaFolder('celebros') . "/" . $name . ".zip");

        return $this->convertPathToUrl($this->getIntMediaFolder('celebros') . "/" . $name . ".zip");
    }

    public function getDataStartExport()
    {
        $storeId = $this->id ? : 1;
        $exportProcessId = (int)$this->helper->getExportProcessId();
        $comm = 'nohup php ' . $this->dir->getRoot() . '/bin/magento celebros:export ' . $storeId . ' ' . $exportProcessId . ' > /dev/null & echo $!';
        $process = new Process($comm);
        $process->start();
        $pid = $process->getPid();

        return ['response' => [
            'status' => self::STARTED,
            'pid' => $pid,
            'export_process_id' => $exportProcessId
        ]];
    }

    public function convertPathToUrl($path)
    {
        $rootPath = $this->dir->getRoot();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        return str_replace($this->dir->getRoot(), $this->storeManager->getStore()->getBaseUrl(), (string) $path);
    }

    public function getExportStatusByStore($store)
    {
        $result = [];
        $storeStatus = self::STARTED;
        $folder = $this->helper->getExportPath($this->id) . '/' . $store->getWebsite()->getCode() . '/' . $store->getCode();
        $fileInfo = new \SplFileInfo($this->helper->getExportPath($this->id));
        $startedAt = $fileInfo->getCTime();
        $updates = [];
        $dir = new \DirectoryIterator($folder);

        foreach ($dir as $fileInfo) {
            if (!$fileInfo->isDir() && !$fileInfo->isDot()) {
                $this->status = self::DONE;
                if ($fileInfo->getExtension() == 'zip') {
                    $result['zip'][] =  $fileInfo->getFilename();
                    $storeStatus = ($storeStatus !== self::IN_PROGRESS) ? self::DONE : $storeStatus;
                } else {
                    $result['files'][] =  $fileInfo->getFilename();
                    $storeStatus = self::IN_PROGRESS;
                }
                $updates[] = $fileInfo->getCTime();
            }
        }

        if ($storeStatus !== self::DONE) {
            if (!empty($updates) && (max($updates) + self::MAX_LAST_UPDATE_AGE) <= time()
            || ($startedAt + self::MAX_PROCESS_AGE) <= time()) {
                $this->status = $storeStatus = self::FAILED;
            } elseif ($storeStatus === self::IN_PROGRESS) {
                $this->status = self::IN_PROGRESS;
            }
        }

        return [
            'started_at' => $this->_prepareDateTime($startedAt),
            'last_updated_at' => !empty($updates) ? $this->_prepareDateTime(max($updates)) : $this->_prepareDateTime($startedAt),
            'status' => $storeStatus,
            'files' => $result
        ];
    }

    protected function _prepareDateTime(string $ts = null)
    {
        $ts = $ts ? : time();
        return  $this->timezone->date($ts)
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    protected function _isProcessStarted()
    {
        try {
            $fileInfo = new \SplFileInfo($this->helper->getExportPath($this->id));
            if ($fileInfo->isDir()) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public function getDataCheckStatus()
    {
        $this->status = self::NOT_EXIST;

        if ($this->_isProcessStarted()) {
            $this->status = self::STARTED;
            $stores = $this->helper->getAllStores();
            foreach ($stores as $store) {
                $result['stores'][$store->getWebsite()->getCode() . "/" . $store->getCode()] = $this->getExportStatusByStore($store);
            }
        }

        $result['status'] = $this->status;
        return ['response' => $result];
    }

    public function startExportProcess(int $storeId = null, int $exportProcessId = null)
    {
        try {
            $this->_prepareExportFolder();
            if ($exportProcessId) {
                $this->exporter->setExportProcessId($exportProcessId);
            }
            $this->exporter->export_celebros(false, $storeId);
            $url = $this->exporter->zipFileName ? : '';
            if ($url) {
                return ['export_url' => $this->_moveAndRenameExportZip($url)];
            } else {
                return [];
            }
        } catch (\Exception $e) {
            $message = sprintf(
                'Export process has failed by the reason: %s',
                $e->getMessage()
            );
            $this->sendNotificationToEmail($message, $storeId);
            throw $e;
        }
    }

    public function sendNotificationToEmail($message, int $storeId)
    {
        $transport = $this->transportBuilder
            ->setTemplateIdentifier('celexport_notification_email_template')
            ->setTemplateOptions(
                [
                    'area' => Area::AREA_ADMINHTML,
                    'store' => $storeId
                ]
            )->setTemplateVars(
                [
                    'message' => $message
                ]
            )
            ->setFromByScope($this->helper->getNotificationFrom($storeId), $storeId)
            ->addTo($this->helper->getNotificationsEmail())
            ->getTransport();
        $transport->sendMessage();

        return $this;
    }
}
