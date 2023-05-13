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

use Magento\Framework\Exception\ConfigurationMismatchException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Celebros\Celexport\Client\Remote;

class Exporter
{
    const ATTR_TABLE_PRODUCT_LIMIT = 1000;
    const ORDER_COLLETION_PAGE_LIMIT = 500;
    const ORDERS_AGE = 120; /* days */
    public const CACHE_LIFETIME = 13600;

    protected $_config;
    protected $_conn;
    protected $_read;
    protected $_fDel;
    protected $_fEnclose;
    protected $_fPath;
    protected $_fType;
    protected $_fStore_id;
    protected $_fStore;
    protected $_fileNameZip;
    protected $_bUpload = true;
    protected $isWebRun = false;
    protected $_exportProcessId = null;
    protected $_resource;
    protected $_product_entity_type_id = null;
    protected $_category_entity_type_id = null;
    protected $_objectManager;
    protected $categoryless_prod_file_name = "categoryless_products";
    protected $prod_file_name = "source_products";
    protected $bExportProductLink = true;
    protected $_dir;
    protected $_shell;
    protected $_productIds = [];
    protected $_categorylessIds = [];
    protected $_attrProductIdsChunks = [];
    protected $_ftpUpload = false;
    protected $_timeMarker = null;
    protected $_row_id = false;
    protected $_productCollection;
    public $zipFileName;

    public $helper;

    public function __construct(
        \Celebros\Celexport\Helper\Export $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Shell $shell,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Framework\App\Cache $cache
    ) {
        $this->helper = $helper;
        $this->_resource = $resource;
        $this->_dir = $dir;
        $this->_shell = $shell;
        $this->_productCollection = $productCollection;
        $this->_read = $this->_resource->getConnection('read');
        $this->cache = $cache;
    }

    protected function logProfiler($msg, $process = null)
    {
        if (!$process) {
            $process = $this->_exportProcessId;
        }

        $this->helper->logProfiler($msg, $process);
    }

    public function getProductEntityIdName($tableName)
    {
        $entityIds = [
            'row_id',
            'entity_id'
        ];
        $table = $this->_resource->getTableName($tableName);
        foreach ($entityIds as $entityId) {
            $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$entityId}'";
            if ($this->_resource->getConnection('read')->fetchOne($sql)) {
                return $entityId;
            }
        }
            return false;
    }

    public function setExportProcessId(int $exportProcessId)
    {
        $this->_exportProcessId = $exportProcessId;
    }

    public function getExportProcessId() : int
    {
        return (int)$this->_exportProcessId;
    }

    public function export_celebros($objectManager, $webAdmin, $storeId = null)
    {
        $this->helper->initExportProcessSettings();
        $this->_product_entity_type_id = $this->get_product_entity_type_id();
        $this->_category_entity_type_id = $this->get_category_entity_type_id();
        $this->isWebRun = $webAdmin;
        $this->_objectManager = $objectManager;
        if (!$this->_exportProcessId) {
            $this->_exportProcessId = $this->helper->getExportProcessId();
        }

        $export_start = microtime(true);
        $this->comments_style('header', 0, 0);
        $this->comments_style('icon', date('Y/m/d H:i:s') . ', Starting profile execution, please wait...', 'icon');
        $this->comments_style('icon', 'Memory Limit: ' . ini_get('memory_limit'), 'icon');
        $this->comments_style('icon', 'Max Execution Time: ' . ini_get('max_execution_time'), 'icon');
        $this->comments_style('warning', 'Warning: Please don\'t close window during importing/exporting data', 'warning');

        if ($this->helper->getConfiguratedEnvStamp() == $this->helper->getCurrentEnvStamp()) {
            $this->_ftpUpload = true;
        }

        $this->cleanExportDirectory();
        $this->export_orders($objectManager->create('Magento\Sales\Model\Order\Item'), $storeId);
        $this->export_main($this->_exportProcessId, $storeId);

        $export_end = microtime(true);

        $this->comments_style('info', 'Finished profile execution within ' . round($export_end - $export_start, 3) . ' sec.', 'finish');
        $this->comments_style('finish', 0, 0);

        return $this->helper->getBodyForResponse();
    }

    public function export_config($store)
    {
        $this->_fStore_id = $store->getStoreId();
        $this->_fStore = $store;
        $this->_fStore_export_enabled = $this->helper->isEnabled($store);

        $this->_fDel = $this->helper->getConfig('celexport/export_settings/delimiter', $store);
        if ($this->_fDel === '\t') {
            $this->_fDel = chr(9);
        }

        $this->_fEnclose = $this->helper->getConfig('celexport/export_settings/enclosed_values', $store);
        $this->_fType = $this->helper->getConfig('celexport/export_settings/type', $store);
        $this->_fPath = $this->helper->getExportPath($this->_exportProcessId) . '/' . $store->getWebsite()->getCode() . '/' . $store->getCode();

        $ftppath = 'ftp_prod';
        $this->_fFTPHost = $ftppath ? $this->helper->getConfig('celexport/' . $ftppath . '/ftp_host', $store) : null;
        $this->_fFTPPort = $ftppath ? $this->helper->getConfig('celexport/' . $ftppath . '/ftp_port', $store) : null;
        $this->_fFTPUser = $ftppath ? $this->helper->getConfig('celexport/' . $ftppath . '/ftp_user', $store) : null;
        $this->_fFTPPassword = $ftppath ? $this->helper->getConfig('celexport/' . $ftppath . '/ftp_password', $store) : null;
        $this->_fFTPPassive = $ftppath ? $this->helper->getConfig('celexport/' . $ftppath . '/passive', $store) : null;
        $this->_fFTPTls = $ftppath ? $this->helper->getConfig('celexport/' . $ftppath . '/tls', $store) : null;

        //feature is not in use
        $this->_fEnableCron = $this->helper->getConfig('celexport/export_settings/cron_enabled');
        $this->CronExpression = $this->helper->getConfig('celexport/export_settings/cron_expr');
        //end
    }

    public function export_orders($orderItems, $storeId = null)
    {
        $stores = $this->helper->getAllStores();
        foreach ($stores as $store) {
            if (!$storeId || $store->getStoreId() == $storeId) {
                $this->helper->setCurrentStore($store->getStoreId());
                $this->export_config($store);
                $this->_createDir($this->_fPath);

                $enclosed = $this->_fEnclose;
                $delimeter = $this->_fDel;
                $newLine = "\r\n";

                if (!$this->helper->isEnabled($store) || !$this->helper->isOrdersExport($store)) {
                    continue;
                }

                if ($storeId && $store->getStoreId() != $storeId) {
                    continue;
                }

                $header = array("OrderID", "ProductSKU", "ProductID", "Date", "Count", "Sum");
                $glue = $enclosed . $delimeter . $enclosed;
                $strResult = $enclosed . implode($glue, $header) . $enclosed . $newLine;

                $strT = time() - 60 * 60 * $this->helper->getConfig('celexport/export_settings/datahistoryperiod', $store) * 24;
                $timeEdge = (new \DateTime(date("Y-m-d", $strT)))->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
                $page = 1;
                do {
                    $orders = $orderItems->getCollection()
                        ->addFieldToFilter('created_at', ['gteq' => $timeEdge])
                        ->addOrder('order_id', 'ASC')
                        ->setPage($page, self::ORDER_COLLETION_PAGE_LIMIT);
                    foreach ($orders as $item) {
                        $record["OrderID"] = $item->getOrderId();
                        $record["ProductSKU"] = $item->getSku();
                        $record["ProductID"] = $item->getProductId();
                        $created_at_time = strtotime((string) $item->getCreatedAt());
                        $record["Date"] = date("Y-m-d", $created_at_time);
                        $record["Count"] = (int)$item->getQtyOrdered();
                        $record["Sum"] = $item->getRowTotal();
                        $strResult .= $enclosed . implode($glue, $record) . $enclosed . $newLine;
                    }
                    $page++;
                } while (count($orders) == self::ORDER_COLLETION_PAGE_LIMIT && $page < 5000);

                //Create, flush, zip and ftp the orders file
                $zipFileName = $this->helper->getDataHistoryFileName($store);

                $this->_createAndUploadOrders($zipFileName, $strResult);

                $this->logProfiler("Exported orders of store {$this->_fStore_id} to file {$zipFileName} . Memory peak was: " . memory_get_peak_usage());
                $this->comments_style('success', "Exported orders of store '{$this->_fStore_id} to file {$zipFileName}'. Memory peak was: " . memory_get_peak_usage(), 'orders');
            }
        }
    }

    public function cleanExportDirectory()
    {
        $dir = $this->helper->getExportPath();
        if (is_dir($dir)) {
            try {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if (filectime($dir . '/' . $file) < strtotime('-' . $this->helper->getExportLifetime() . ' day')
                    && !in_array($file, array('.', '..'))) {
                        $this->removeDir($dir . '/' . $file);
                    }
                }
            } catch (\Exception $e) {
                $this->logProfiler($e->getMessage());
                return false;
            }
        }

        return true;
    }

    public function removeDir($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->removeDir($dir . '/' . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

    public function comments_style($kind, $text, $alt = null)
    {
        if (!$this->isWebRun) {
            return;
        }

        $this->helper->comments_style($kind, $text, $alt);
    }

    protected function _createAndUploadOrders($zipFileName, $str)
    {
        //Create directory to put the file
        if (!$this->_createDir($this->_fPath)) {
            $this->comments_style('error', 'Could not create the directory in ' . $this->_fPath . ' path', 'problemwith dir');
            return;
        }

        $filePath = $this->_fPath . "/Data_History.txt";
        $zipFilePath = $this->_fPath . "/" . $zipFileName;

        //Create file
        if ((!$fh = $this->_createFile($filePath))) {
            $this->comments_style('error', 'Could not create the file in ' . $filePath, 'problemwith file');
            return;
        }

        //Flush string orders data to file
        $this->_stringToTextFile($str, $fh);
        fclose($fh);

        //Zip file
        $this->_zipFile($filePath, $zipFilePath);

        //Ftp file
        if ($this->_fType==="ftp" && $this->_bUpload) {
            $ftpRes = $this->remoteUpload($zipFilePath);
            if (!$ftpRes) {
                $this->comments_style('error', 'Could not upload ' . $zipFilePath . ' to ftp', 'Could_not_upload_to_ftp');
            }
        }
    }

    protected function _createDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            $dir = mkdir($dirPath, 0777, true);
        }

        return $dirPath;
    }

    protected function _createFile($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $fh = fopen($filePath, 'ab');
        return $fh ;
    }

    protected function _stringToTextFile($str, $fh)
    {
        fwrite($fh, (string) $str);
    }

    protected function _zipFile($filePath, $zipFilePath)
    {
        $out = false;
        if (!file_exists($filePath)) {
            $this->comments_style('error', 'No ' . $filePath . ' file found', 'No_txt_file_found');
            return false;
        }

        try {
            $zip = new \ZipArchive();
        } catch (\Exception $e) {
            $this->comments_style('error', 'ZipArchive is not installed', 'ZipArchive is not installed');
            return false;
        }

        if ($zip->open($zipFilePath, \ZipArchive::CREATE) == true) {
            $out = $zip->addFile($filePath, basename((string) $filePath));
            if (!$out) {
                $this->comments_style('error', 'Could not add ' . $filePath . 'to zip archive', 'Could_not_add_txt_file_to_zip_file');
            }

            $zip->close();
            unlink($filePath);
        } else {
            $this->comments_style('error', 'Could not create ' . $zipFilePath . ' file', 'Could_not_create_zip_file');
        }

        return $out;
    }

    public function export_main($exportProcessId = 0, $storeId = null)
    {
        $this->_exportProcessId = $exportProcessId;
        $stores = $this->helper->getAllStores();
        foreach ($stores as $store) {
            if (!$storeId || $store->getStoreId() == $storeId) {
                $this->helper->setCurrentStore($store->getStoreId());
                if (!$this->helper->isEnabled($store) && !$storeId) {
                    $this->comments_style('info', "Export not enabled for store view: {$store->getName()}", 'STORE');
                    continue;
                }

                $this->_fStore_id = $store->getStoreId();
                $this->export_config($store);
                $this->_createDir($this->_fPath);

                $this->_fileNameZip =$this->helper->getConfig('celexport/export_settings/zipname', $store);

                $this->comments_style('section', "Store code: {$this->_fStore_id}, name: {$store->getName()}", 'STORE');
                $this->comments_style('section', "Zip file name: {$this->_fileNameZip}", 'STORE');

                //Resetting store categories mapping.
                $this->_categoriesForStore = false;
                $this->_categoriesForStore = implode(',', $this->_getAllCategoriesForStore());

                $this->logProfiler('===============');
                $this->logProfiler('Starting Export');
                $this->logProfiler('===============');
                $this->logProfiler('memory_limit: ' . ini_get('memory_limit'));
                $this->logProfiler('max_execution_time: ' . ini_get('max_execution_time'));
                $this->logProfiler('===============');
                $this->logProfiler("Store code: {$this->_fStore_id}, name: {$store->getName()}");
                $this->logProfiler("Zip file name: {$this->_fileNameZip}");
                $this->logProfiler('Mem usage: ' . memory_get_usage(true));

                $this->comments_style('icon', "Memory usage: " . memory_get_usage(true), 'icon');
                $this->comments_style('icon', 'Exporting tables', 'icon');
                $this->comments_style('info', "Memory usage: " . memory_get_usage(true), 'info');

                $this->logProfiler('Exporting tables');
                $this->logProfiler('----------------');

                $this->export_tables($store);

                $this->comments_style('info', "Memory usage: " . memory_get_usage(true), 'info');

                //Only run export products if there are categories assigned to the current store view.
                if ($this->_categoriesForStore) {
                    $this->comments_style('icon', 'Exporting products', 'icon');
                    $this->comments_style('info', "Memory usage: " . memory_get_usage(true), 'info');
                    $this->getTimeOffset(microtime(true));
                    $this->logProfiler('Writing products file');
                    $this->logProfiler('---------------------');

                    $this->export_store_products($store);
                }

                //Running over the products that aren't assigned to a category separately.
                $this->comments_style('icon', 'Exporting category-less products', 'icon');
                $this->comments_style('info', "Memory usage: " . memory_get_usage(true), 'info');

                $this->logProfiler('Writing category-less products file');
                $this->logProfiler('-----------------------------------');
                $this->export_categoryless_products($store);

                $this->comments_style('icon', 'Creating ZIP file', 'icon');
                $this->comments_style('info', "Memory usage: " . memory_get_usage(true), 'info');

                $this->logProfiler('Creating ZIP file');
                $this->logProfiler('-----------------');

                $zipFilePath = $this->zipLargeFiles();
                $this->zipFileName = $zipFilePath;

                $this->comments_style('icon', 'Checking FTP upload', 'icon');
                $this->comments_style('info', "Memory usage: " . memory_get_usage(true), 'info');

                if ($this->_fType === "ftp"/* && $this->_bUpload*/) {
                    $this->comments_style('info', 'Uploading export file', 'info');
                    $ftpRes = $this->remoteUpload($zipFilePath);
                    if (!$ftpRes) {
                        $this->comments_style('info', "Could not upload " . $zipFilePath . ' to ftp', 'info');
                        $this->logProfiler('FTP upload ERROR');
                    } else {
                        $this->logProfiler('FTP upload success');
                    }
                } else {
                    $this->comments_style('info', 'No need to upload export file', 'info');
                    $this->logProfiler('No need to upload export file');
                }

                $this->comments_style('icon', 'Finished', 'icon');
                $this->comments_style('info', "Memory usage: " . memory_get_usage(true), 'info');
                $this->comments_style('info', "Memory peek usage: " . memory_get_peak_usage(true), 'info');
                $this->comments_style('icon', date('Y/m/d H:i:s'), 'icon');

                $this->logProfiler('Mem usage: ' . memory_get_usage(true));
                $this->logProfiler('Mem peek usage: ' . memory_get_peak_usage(true));
            }
        }
    }

    public function getEntityIdByRowId($row_id)
    {
        return (isset($this->_rowEntityMap[$row_id]) ? $this->_rowEntityMap[$row_id] : $row_id);
    }

    public function getRowIdByEntityId($entity_id)
    {
        return (isset($this->_entityRowMap[$entity_id]) ? $this->_entityRowMap[$entity_id] : $entity_id);
    }

    public function getEntityIdByRowIdCat($row_id)
    {
        return (isset($this->_rowEntityMapCat[$row_id]) ? $this->_rowEntityMapCat[$row_id] : $row_id);
    }

    public function getRowIdByEntityIdCat($entity_id)
    {
        return (isset($this->_entityRowMapCat[$entity_id]) ? $this->_entityRowMapCat[$entity_id] : $entity_id);
    }

    public function arrayToString($fields)
    {
        return "^" . implode("^" . $this->_fDel . "^", $fields) . "^" . "\r\n";
    }

    protected function export_tables($store)
    {
        $rowEntityMap = [];
        $entityName = $this->getProductEntityIdName("catalog_product_entity");
        if ($entityName == 'row_id') {
            $this->_row_id = true;
        }

        $table = $this->_resource->getTableName("catalog_product_entity");
        $query = $this->_read->select();
        if ($entityName == 'row_id') {
            $query->from(
                $table,
                array('entity_id', 'row_id')
            )->group('row_id');

            $rows = $query->query();
            while ($row = $rows->fetch()) {
                $rowEntityMap[$row['row_id']] = $row['entity_id'];
            }
        } else {
            $query->from(
                $table,
                array('entity_id')
            )->group('entity_id');

            $rows = $query->query();
            while ($row = $rows->fetch()) {
                $rowEntityMap[$row['entity_id']] = $row['entity_id'];
            }
        }

        $this->_rowEntityMap = $rowEntityMap;
        $this->_entityRowMap = array_flip($rowEntityMap);

        $rowEntityMap = [];
        $entityName = $this->getProductEntityIdName("catalog_category_entity");
        $table = $this->_resource->getTableName("catalog_category_entity");
        $query = $this->_read->select();
        if ($entityName == 'row_id') {
            $query->from(
                $table,
                array('entity_id', 'row_id')
            )->group('row_id');

            $rows = $query->query();
            while ($row = $rows->fetch()) {
                $rowEntityMap[$row['row_id']] = $row['entity_id'];
            }
        } else {
            $query->from(
                $table,
                array('entity_id')
            )->group('entity_id');

            $rows = $query->query();
            while ($row = $rows->fetch()) {
                $rowEntityMap[$row['entity_id']] = $row['entity_id'];
            }
        }

        $this->_rowEntityMapCat = $rowEntityMap;
        $this->_entityRowMapCat = array_flip($rowEntityMap);

        /*----- catalog_eav_attribute.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_eav_attribute");
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array('attribute_id', 'is_searchable', 'is_filterable', 'is_comparable')
        );
        $this->export_table($query, "catalog_eav_attribute");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- attributes_lookup.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("eav_attribute");
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array('attribute_id', 'attribute_code', 'backend_type', 'frontend_input')
        )->where('entity_type_id = ?', $this->_product_entity_type_id);
        $this->export_attributes_table($query, "attributes_lookup");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_entity.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_entity");
        $this->logProfiler("START {$table}");
        $entityName = $this->getProductEntityIdName("catalog_product_entity");
        $categories = implode(',', $this->_getAllCategoriesForStore());
        $categoryProductsTable = $this->_resource->getTableName("catalog_category_product");
        $query = $this->_read->select()->from(
            $table,
            array($entityName, 'type_id', 'sku')
        )->joinLeft(
            $categoryProductsTable,
            "`{$table}`.`{$entityName}` = `{$categoryProductsTable}`.`product_id`",
            array()
        )->group($entityName);
        $this->export_table($query, "catalog_product_entity");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- disabled_products.txt -----*/
        $this->exportProdIdsByAttributeValue(
            'status',
            \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED,
            $this->_fStore_id,
            'disabled_products'
        );

        /*----- not_visible_individually_products.txt -----*/
        $this->exportProdIdsByAttributeValue(
            'visibility',
            \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE,
            $this->_fStore_id,
            'not_visible_individually_products'
        );

        /*----- catalog_product_entity_varchar.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_entity_varchar");
        $idName = $this->getProductEntityIdName($table);
        $this->logProfiler("START {$table}");
        $sql = $this->_read->select()->from(
            $table,
            array($idName, 'value', 'attribute_id')
        );
        $this->export_product_att_table($sql, "catalog_product_entity_varchar", $idName);
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_entity_int.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_entity_int");
        $idName = $this->getProductEntityIdName($table);
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array($idName, 'value', 'attribute_id')
        );
        $this->export_product_att_table($query, "catalog_product_entity_int", $idName);
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_entity_text.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_entity_text");
        $idName = $this->getProductEntityIdName($table);
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array($idName, 'value', 'attribute_id')
        );
        $this->export_product_att_table($query, "catalog_product_entity_text", $idName);
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_entity_decimal.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_entity_decimal");
        $idName = $this->getProductEntityIdName($table);
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array($idName, 'value', 'attribute_id')
        );
        $this->export_product_att_table($query, "catalog_product_entity_decimal", $idName);
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_entity_datetime.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_entity_datetime");
        $idName = $this->getProductEntityIdName($table);
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array($idName, 'value', 'attribute_id')
        );
        $this->export_product_att_table($query, "catalog_product_entity_datetime", $idName);
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- eav_attribute_option_value.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("eav_attribute_option_value");
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array('option_id', 'value')
        );
        $this->export_table($query, "eav_attribute_option_value", array('option_id'));
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- eav_attribute_option.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("eav_attribute_option");
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array('option_id', 'attribute_id')
        );
        $this->export_table($query, "eav_attribute_option");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_category_product.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_category_product");
        $this->logProfiler("START {$table}");
        $categories = implode(',', $this->_getAllCategoriesForStore());
        $query = $this->_read->select()->from(
            $table,
            array('category_id', 'product_id')
        )->where("`category_id` IN ({$categories})");
        $this->export_table($query, "catalog_category_product");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_category_entity.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_category_entity");
        $idName = $this->getProductEntityIdName($table);
        $this->logProfiler("START {$table}");
        $categories = implode(',', $this->_getAllCategoriesForStore($idName));
        $query = $this->_read->select()->from(
            $table,
            array($idName, 'parent_id', 'path')
        )->where("`" . $idName . "` IN ({$categories})");
        $this->export_table($query, "catalog_category_entity");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- category_lookup.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_category_entity_varchar");
        $this->logProfiler("START {$table}");
        $categories = $this->_getAllCategoriesForStore($this->getProductEntityIdName($table));
        $this->exportLookupCategories("category_lookup", $categories);
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- disabled_categories.txt -----*/
        $this->getTimeOffset(microtime(true));
        $this->logProfiler("START {$table}");
        $this->exportDisabledCategories("disabled_categories");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_super_link.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_category_entity");
        $idName = $this->getProductEntityIdName($table);

        $table = $this->_resource->getTableName("catalog_product_super_link");
        $this->logProfiler("START {$table}");
        $fields = ['product_id', 'parent_id'];
        $str = $this->arrayToString($fields);
        $query = $this->_read->select();
        $query->from(
            $table,
            $fields
        );

        $rows = $query->query();
        while ($row = $rows->fetch()) {
            /*$fields = [$row['product_id'], $this->getEntityIdByRowId($row['parent_id'])];*/
            $fields = [$this->getRowIdByEntityId($row['product_id']), $row['parent_id']];
            $str .= $this->arrayToString($fields);
        }

        $filename = 'catalog_product_super_link';
        $fh = $this->create_file($filename);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file ' . $filename . ' path', 'problem with file');
            $this->logProfiler('Could not create the file ' . $filename . ' path');
            return;
        }

        $this->write_to_file($str, $fh);
        fclose($fh);

        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_relation.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_relation");
        $this->logProfiler("START {$table}");
        $fields = ['parent_id', 'child_id'];
        $str = $this->arrayToString($fields);
        $query = $this->_read->select();
        $query->from(
            $table,
            $fields
        );

        $rows = $query->query();
        while ($row = $rows->fetch()) {
            /*$fields = [$this->getEntityIdByRowId($row['parent_id']), $row['child_id']];*/
            $fields = [$row['parent_id'], $this->getRowIdByEntityId($row['child_id'])];
            $str .= $this->arrayToString($fields);
        }

        $filename = 'catalog_product_relation';
        $fh = $this->create_file($filename);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file ' . $filename . ' path', 'problem with file');
            $this->logProfiler('Could not create the file ' . $filename . ' path');
            return;
        }

        $this->write_to_file($str, $fh);
        fclose($fh);

        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_super_attribute.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_super_attribute");
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array('product_id', 'attribute_id')
        );
        $this->export_table($query, "catalog_product_super_attribute");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- review_entity.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("review_entity");
        $product_entity_id = $this->_read->select()->from(
            $table,
            array('entity_id')
        )->where("`entity_code` = 'product'")
        ->query()->fetch();
        $table = $this->_resource->getTableName("review_entity_summary");
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array('entity_pk_value', 'reviews_count', 'rating_summary')
        )->where("`entity_type` = '{$product_entity_id['entity_id']}'");
        $this->export_table($query, "review_entity", array('entity_pk_value'));
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        /*----- catalog_product_website.txt -----*/
        $this->getTimeOffset(microtime(true));
        $table = $this->_resource->getTableName("catalog_product_website");
        $this->logProfiler("START {$table}");
        $query = $this->_read->select()->from(
            $table,
            array('product_id')
        )->where('website_id = ?', $this->_fStore->getWebsiteId());
        $this->export_table($query, "catalog_product_website");
        $this->logProfiler("FINISH {$table}");
        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler('------------------------------------');

        $this->export_extra_tables($store);
    }

    protected function exportDisabledCategories($filename)
    {
        $table = $this->_resource->getTableName("catalog_category_entity");
        $idName = $this->getProductEntityIdName($table);
        $categories = $this->_objectManager->create('Magento\Catalog\Model\Category')
            ->getCollection()->addAttributeToSelect('is_active')
            ->setProductStoreId($this->_fStore_id);

        $fh = $this->create_file($filename);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file ' . $filename . ' path', 'problem with file');
            $this->logProfiler('Could not create the file ' . $filename . ' path');
            return;
        }

        $str = "^" . $idName . "^\r\n";

        foreach ($categories as $category) {
            if (!$category->getIsActive()) {
                $str .= "^" . $category->getData($idName) . "^" . "\r\n";
            }
        }

        $this->write_to_file($str, $fh);
        fclose($fh);
    }

    protected function exportLookupCategories($filename, $categoriesIds = array())
    {
        $table = $this->_resource->getTableName("catalog_category_entity");
        $idName = $this->getProductEntityIdName($table);
        $categories = $this->_objectManager->create('Magento\Catalog\Model\Category')
            ->getCollection()
            ->addAttributeToSelect(['entity_id','name'])
            ->addFieldToFilter($idName, ['in' => $categoriesIds]);

        $fh = $this->create_file($filename);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file ' . $filename . ' path', 'problem with file');
            $this->logProfiler('Could not create the file ' . $filename . ' path');
            return;
        }

        $str = "^" . $idName ."^" . $this->_fDel . "^value^\r\n";
        foreach ($categories as $category) {
            $str .= "^" . $category->getData($idName) . "^" . $this->_fDel . "^" . $category->getName() . "^" . "\r\n";
        }

        $this->write_to_file($str, $fh);
        fclose($fh);
    }

    protected function export_table($sql, $filename, $main_fields = null)
    {
        $fh = $this->create_file($filename);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file ' . $filename . ' path', 'problem with file');
            $this->logProfiler('Could not create the file ' . $filename . ' path');
            return;
        }

        $this->write_headers($sql, $fh);

        $sql->limit(100000000, 0);

        //This part is only for tables that should be run twice - once with the store view, and again with the default.
        if (isset($main_fields)) {
            //preparing the query for the second run on the default store view.
            $secondSql = clone($sql);

            //On the first run, we'll only get the current store view.
            $sql->where('store_id = ?', $this->_fStore_id);
        }

        //Run the actual process of getting the rows and inserting them to the file,
        // and output the list of rows you covered to $processedRows.
        $processedRows = $this->export_table_rows($sql, $main_fields, $fh);

        //This part is only for tables that should be run twice - once with the store view, and again with the default.
        if (isset($main_fields)) {
            //Specifying the default store view.
            $secondSql->where('store_id = 0');

            //Only add the where statement in case items were found in the first run.
            if (count($processedRows)) {
                $concat_fields = implode('-', $main_fields);
                $secondSql->where("CONCAT({$concat_fields}) NOT IN (?)", $processedRows);
            }

            //Run the actual process of getting each row again, this time selecting rows with the default store view.
            $this->export_table_rows($secondSql, null, $fh);
        }

        fclose($fh);
    }

    protected function create_file($name, $ext = "txt")
    {
        try {
            if (!is_dir($this->_fPath)) {
                $dir = mkdir($this->_fPath, 0777, true);
            }
            $filePath = $this->_fPath . '/' . $name . "." . $ext;
            $fh = fopen($filePath, 'wb');
        } catch (\Exception $e) {
            $this->comments_style('error', 'Could not create export directory or files.', 'file permissions');
            $this->logProfiler('Failed creating the export files or directory.');
            return;
        }

        return $fh;
    }

    protected function write_headers($sql, $fh)
    {
        $header = "^";
        $columns = $sql->getPart('columns');
        $fields = array();
        foreach ($columns as $column) {
            if ($column[1] != '*') {
                $fields[] = $column[1];
            } else {
                $read = $this->_resource->getConnection('read');
                $fields = array_merge($fields, array_keys($read->describeTable($this->_resource->getTableName($columns[0][0]))));
            }
        }
        $header .= implode("^" . $this->_fDel . "^", $fields);
        $header .= "^" . "\r\n";
        $this->write_to_file($header, $fh);

        return $columns;
    }

    protected function write_to_file($str, $fh)
    {
        fwrite($fh, (string) $str);
    }

    protected function export_table_rows($sql, $fields, $fh)
    {
        $str = "";

        $query = $sql->query();
        $rowCount = 0;
        $processedRows = array();
        while ($row = $query->fetch()) {
            //$this->logProfiler("Block read start ({$this->_limit} products");
            //$this->logProfiler('Mem usage: ' . memory_get_usage(TRUE));

            /*if (isset($row['row_id']) && isset($this->_rowEntityMap[$row['row_id']])) {
                $tmp = ['entity_id' => $this->_rowEntityMap[$row['row_id']]];
                unset($row['row_id']);
                $row = array_merge($tmp, $row);
            }*/

            /* catalog_category_products */
            if (isset($row['category_id']) && isset($row['product_id'])) {
                $row['category_id'] = $this->getRowIdByEntityIdCat($row['category_id']);
                $row['product_id'] = $this->getRowIdByEntityId($row['product_id']);
            }

            /* catalog_category_entity */
            if (isset($row['row_id']) && isset($row['path'])) {
                if (isset($row['parent_id'])) {
                    $row['parent_id'] = $this->getRowIdByEntityIdCat($row['parent_id']);
                }

                $tmp = explode("/", (string)$row['path']);
                foreach ($tmp as $key => $entity_id) {
                    $tmp[$key] = $this->getRowIdByEntityIdCat($entity_id);
                }

                $row['path'] = implode("/", $tmp);
            }

            //remember all the rows we're processing now, so we won't go over them again when we iterate over the default store.
            if (isset($fields)) {
                $concatenatedRow = '';
                foreach ($fields as $field) {
                    $concatenatedRow .= $row[$field] . '-';
                }

                $processedRows[] = substr($concatenatedRow, 0, -1);
            }

            $str .= "^" . implode("^" . $this->_fDel . "^", $row) . "^" . "\r\n";
            $rowCount++;

            if (($rowCount%1000)==0) {
                //$this->logProfiler("Write block start");
                $this->write_to_file($str, $fh);
                //$this->logProfiler("Write block end");
                $str="";
            }
        }

        if (($rowCount%1000)!=0) {
            //$this->logProfiler("Write last block start");
            $this->write_to_file($str, $fh);
            //$this->logProfiler("Write last block end");
        }

        //$this->logProfiler("Total rows: {$rowCount}");

        return $processedRows;
    }

    protected function export_attributes_table($sql, $filename)
    {
        $fh = $this->create_file($filename);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file ' . $filename . ' path', 'problem with file');
            $this->logProfiler('Could not create the file ' . $filename . ' path');
            return;
        }

        //Adding another column header before the call to write_headers().
        $columns = $sql->getPart('columns');
        $sql->columns('frontend_label');
        $this->write_headers($sql, $fh);
        $sql->setPart('columns', $columns);

        $sql->limit(100000000, 0);

        //Preparing the select object for the second query.
        $secondSql = clone($sql);

        //Adding a join statement to the first run alone, to get labels from eav_attribute_label.
        $table = $sql->getPart('from');
        $table = array_shift($table);
        $labelTable = $this->_resource->getTableName("eav_attribute_label");
        $sql->joinLeft(
            $labelTable,
            "{$table['tableName']}.`attribute_id` = `{$labelTable}`.`attribute_id` AND `{$labelTable}`.`store_id` = {$this->_fStore_id}",
            array('value')
        )->where("`{$labelTable}`.`value` IS NOT NULL")
        ->group('attribute_id');

        //Process the rows that are covered by eav_attribute_label.
        $processedRows = $this->export_table_rows($sql, array('attribute_id'), $fh);

        //run a second time with only ids that are not in the list from the first run.
        $secondSql->columns('frontend_label');
        if (count($processedRows)) {
            $secondSql->where("`attribute_id` NOT IN (?)", $processedRows);
        }

        $this->export_table_rows($secondSql, null, $fh);

        fclose($fh);
    }

    protected function export_product_att_table($originalSql, $filename, $entityIdName = 'entity_id')
    {
        $fh = $this->create_file($filename);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file ' . $filename . ' path', 'problem with file');
            $this->logProfiler('Could not create the file ' . $filename . ' path');
            return;
        }

        $columns = $this->write_headers($originalSql, $fh);
        $originalSql->limit(100000000, 0);

        $page = 1;
        $chunksIds = [];

        if (null == isset($this->_attrProductIdsChunks[$this->_fStore_id])) {
            $this->_attrProductIdsChunks[$this->_fStore_id] = [];
            do {
                $productIds = $this->_objectManager->create('Magento\Catalog\Model\Product')
                    ->getCollection()
                    ->addStoreFilter($this->_fStore_id)
                    ->addOrder($entityIdName, "ASC")
                    ->setPage($page, self::ATTR_TABLE_PRODUCT_LIMIT)
                    ->getColumnValues($entityIdName);
                $this->_attrProductIdsChunks[$this->_fStore_id][] = $productIds;
                $page++;
            } while (count($productIds) == self::ATTR_TABLE_PRODUCT_LIMIT && $page < 400);
        }

        foreach ($this->_attrProductIdsChunks[$this->_fStore_id] as $ids) {
            if (is_array($ids) && !empty($ids)) {
                $sql = clone($originalSql);
                $table = $sql->getPart('from');
                $table = array_shift($table);
                $relevant_products = implode(',', $ids);;
                $sql->where("{$table['tableName']}.`{$entityIdName}` IN ({$relevant_products})");

                $secondSql = clone($sql);

                $sql->where('`store_id` = ?', $this->_fStore_id);

                //Get list of rows with this specific store view, to exclude when running on the default store view.
                $sql->columns($entityIdName);
                $sql->columns('attribute_id');
                $query = $sql->query();
                $processedRows = array();
                while ($row = $query->fetch()) {
                    $processedRows[] = $row['attribute_id'] . '-' . $row[$entityIdName];
                }
                $sql->setPart('columns', $columns);
                $sql->order($entityIdName, 'ASC');

                //Run the query on each row and save results to the file.
                $this->export_table_rows($sql, null, $fh);

                //Prepare the second query.
                $secondSql->where('store_id = 0');
                if (count($processedRows)) {
                    $secondSql->where("CONCAT(`attribute_id`, '-', `{$entityIdName}`) NOT IN (?)", $processedRows);
                }

                $secondSql->order($entityIdName, 'ASC');

                //Run for the second time, now with the default store view.
                $this->export_table_rows($secondSql, null, $fh);
            }
        }

        fclose($fh);
    }

    protected function get_product_entity_type_id()
    {
        $table = $this->_resource->getTableName("eav_entity_type");
        $sql = "SELECT entity_type_id
        FROM {$table}
        WHERE entity_type_code='catalog_product'";

        return $this->_resource->getConnection('read')->fetchOne($sql);
    }

    protected function get_category_entity_type_id()
    {
        $table = $this->_resource->getTableName("eav_entity_type");
        $sql = "SELECT entity_type_id
        FROM {$table}
        WHERE entity_type_code='catalog_category'";

        return $this->_resource->getConnection('read')->fetchOne($sql);
    }

    protected function _getAllCategoriesForStore($field = 'entity_id')
    {
        $table = $this->_resource->getTableName("catalog_category_entity");
        $sql2 = $this->_read->select()->from($table, array($field, 'path'));

        $results = $this->_read->fetchPairs($sql2);
        $rootCategoryId = $this->_fStore->getRootCategoryId();
        $categories = [];
        foreach ($results as $entity_id => $path) {
            $path = explode('/', (string)$path);
            if (count($path) > 1) {
                if ($path[1] == $rootCategoryId) {
                    $categories[] = $entity_id;
                }
            } else {
                $categories[] = $entity_id;
            }
        }

        return $categories;
    }

    public function zipLargeFiles()
    {
        $out = false;
        $zipPath = $this->_fPath . '/' . $this->_fileNameZip;

        try {
            $dh = opendir($this->_fPath);
        } catch (\Exception $e) {
            $this->comments_style('error', 'Could not open folder for archiving.', 'problem with folder');
            $this->logProfiler('Could not open folder for archiving.');
            return;
        }

        $filesToZip = array();
        while (($item = readdir($dh)) !== false && !is_null($item)) {
            $filePath = $this->_fPath . '/' . $item;
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            if (is_file($filePath) && ($ext == "txt" || $ext == "log")) {
                $filesToZip[] = $filePath;
            }
        }

        for ($i=0; $i < count($filesToZip); $i++) {
            $filePath = $filesToZip[$i];
            $out = $this->zipLargeFile($filePath, $zipPath);
        }

        return $out ? $zipPath : false;
    }

    public function zipLargeFile($filePath, $zipPath)
    {
        $out = false;

        try {
            $zip = new \ZipArchive();
        } catch (\Exception $e) {
            $this->comments_style('error', 'ZipArchive is not installed', 'ZipArchive is not installed');
            return $out;
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE) == true) {
            $fileName = basename((string) $filePath);
            $out = $zip->addFile($filePath, basename((string) $filePath));
            if (!$out) {
                throw new \Exception("Could not add file '{$fileName}' to_zip_file");
            }

            $zip->close();
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            if ($ext != "log") {
                unlink($filePath);
            }
        } else {
            throw new \Exception("Could not create zip file");
        }

        return $out;
    }

    private function remoteUpload(
        string $zipFilePath
    ): bool {
        $remote = new Remote();
        try {
            $remote->send($this->collectIOConfig(), $zipFilePath);
        } catch (ConfigurationMismatchException $e) {
            $this->comments_style('error', $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @throws ConfigurationMismatchException
     * @return array
     */
    private function collectIOConfig(): array
    {
        if (!$this->_ftpUpload) {
            throw new ConfigurationMismatchException(__('Env stamp is incorrect - ftp upload is not available'));
        }

        if (!$this->_fFTPHost) {
            throw new ConfigurationMismatchException(__('Empty host specified'));
        }

        $ioConfig = [];
        if ($this->_fFTPHost != '') {
            $ioConfig['host'] = $this->_fFTPHost;
        }

        if ($this->_fFTPPort != '') {
            $ioConfig['port'] = $this->_fFTPPort;
        }

        if ($this->_fFTPUser != '') {
            $ioConfig['user'] = $this->_fFTPUser;
        } else {
            $ioConfig['user']='anonymous';
            $ioConfig['password']='anonymous@noserver.com';
        }

        if ($this->_fFTPPassword != '') {
            $ioConfig['password'] = $this->_fFTPPassword;
        }

        $ioConfig['passive'] = $this->_fFTPPassive;
        $ioConfig['ssl'] = $this->_fFTPTls;

        return $ioConfig;
    }

    public function uploadLog($connection)
    {
        $logfilename = $this->helper->getLogFilename($this->_exportProcessId);
        if (file_exists($this->helper->getExportPath() . $logfilename)) {
            ftp_put($connection, 'celebros.log', $this->helper->getExportPath() . $logfilename, FTP_BINARY);
        }
    }

    protected function getCategoryIsActiveAttributeId()
    {
        $table = $this->_resource->getTableName("eav_attribute");
        $sql = "SELECT attribute_id
        FROM {$table}
        WHERE entity_type_id ={$this->_category_entity_type_id} AND attribute_code='is_active'";

        return $this->_resource->getConnection('read')->fetchOne($sql);
    }

    protected function getCategoryNnameAttributeId()
    {
        $table = $this->_resource->getTableName("eav_attribute");
        $sql = "SELECT attribute_id
        FROM {$table}
        WHERE entity_type_id ={$this->_category_entity_type_id} AND attribute_code='name'";

        return $this->_resource->getConnection('read')->fetchOne($sql);
    }

    protected function exportProdIdsByAttributeValue($attribute_code, $attribute_value, $store_id, $filename)
    {
        $collection = $this->_objectManager->create('Magento\Catalog\Model\Product')->getCollection();
        $collection->setStoreId($store_id)
            ->addStoreFilter($store_id)
            ->addAttributeToFilter($attribute_code, $attribute_value);

        $fh = $this->create_file($filename);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file ' . $filename . ' path', 'problem with file');
            $this->logProfiler('Could not create the file ' . $filename . ' path');
            return;
        }

        $idName = ($this->_row_id) ? 'row_id' : 'entity_id';
        $str = "^" . $idName . "^\r\n";

        foreach ($collection as $item) {
            $str .= "^" . $item->getData($idName) . "^" . "\r\n";
        }

        $this->write_to_file($str, $fh);
        fclose($fh);
    }

    protected function export_extra_tables($store)
    {
        $this->comments_style('icon', "Exporting extra tables", 'icon');
        $extraTablesData = (string)$this->helper->getConfig('celexport/export_settings/extra_tables', $store) ?? '';
        $extraTables = explode("\n", $extraTablesData);
        foreach ($extraTables as $table) {
            if (trim($table)=='') {
                continue;
            }

            try {
                $tableName = $this->_resource->getTableName(trim($table));
            } catch (\Exception $ex) {
                $this->comments_style('error', "Table '{$table}' does not exist", 'error');
                continue;
            }

            $tableExists = $this->_read->isTableExists($tableName);

            if ($tableExists) {
                $this->comments_style('info', "Exporting table '{$tableName}'", 'info');
                $query = $this->_read->select()->from($tableName, array('*'));
                $this->export_table($query, $tableName);
            } else {
                $this->comments_style('error', "Table '{$table}'='{$tableName}' does not exist", 'error');
            }
        }
    }

    protected function prepareStoreProductIds($store)
    {
        $rootCategoryId = $store->getRootCategoryId();
        $productIds = [];
        $categorylessIds = [];

        $page = 1;
        $chunksIds = [];
        $entityName = $this->getProductEntityIdName("catalog_product_entity");

        do {
            $products = $this->_productCollection->create();
            $products->setStoreId($store->getStoreId())
                ->addStoreFilter($store->getStoreId())
                ->addCategoryIds()
                ->addOrder($entityName, "ASC")
                ->setPage($page, self::ATTR_TABLE_PRODUCT_LIMIT);

            foreach ($products as $prod) {
                if (!empty($prod->getCategoryIds())) {
                    $productIds[] = $prod->getData($entityName);
                } else {
                    $categorylessIds[] = $prod->getData($entityName);
                }
            }

            $page++;
        } while (count($products) == self::ATTR_TABLE_PRODUCT_LIMIT && $page < 400);

        $this->_productIds = array_unique($productIds);
        $this->_categorylessIds = array_unique($categorylessIds);
    }

    protected function export_store_products($store)
    {
        $this->prepareStoreProductIds($store);
        $this->export_products($this->_productIds, $this->prod_file_name, $store);
    }

    protected function export_categoryless_products($store)
    {
        /*$this->prepareStoreProductIds($store);*/
        $this->export_products($this->_categorylessIds, $this->categoryless_prod_file_name, $store);
    }

    protected function export_products($productIds, $fileName, $store)
    {
        $this->getTimeOffset(microtime(true));
        $this->comments_style('info', "Begining products export", 'info');
        $this->comments_style('info', "Memory usage: " . memory_get_usage(true), 'info');

        $this->logProfiler("START export products");
        $startTime = time();

        $fields = array('mag_id', 'price', 'type_id', 'sku');

        if ($this->_row_id) {
            $fields[] = 'entity_id';
        }

        if ($this->bExportProductLink) {
            $fields[] = 'link';
        }

        $prodParams = $this->helper->getProdParams($this->_objectManager);
        foreach ($prodParams as $prodParam) {
            $fields[] = (string)$prodParam['label'];
        }

        $imageTypes = $this->helper->getImageTypes($this->_objectManager);
        foreach ($imageTypes as $imgType) {
            $fields[] = (string)$imgType['label'];
        }

        //Fetching a list of custom attributes, for which we'll need to map out the values from the corresponding source models.
        $customAttributes = $this->_getCustomAttributes();
        foreach ($customAttributes as $key => $customAttribute) {
            $customAttributes[$key] = $customAttribute['attribute_code'];
            $fields[] = $customAttribute['attribute_code'] . '_value';
        }

        //Creating a custom fields cache for use in the separate processes.
        $this->cache->save(
            json_encode($customAttributes),
            'export_custom_fields_' . $this->_exportProcessId,
            [],
            self::CACHE_LIFETIME
        );

        //Dispatching event in case a custom module would want to modify the export process.
        /*Mage::dispatchEvent('celexport_before_export_products', array(
            'fields'   => &$fields,
            'sql'      => &$sql,
            'filename' => &$fileName
        ));*/

        //Creating the file handler to save the export results and handling any errors that might occur in the process.
        $fh = $this->create_file($fileName);
        if (!$fh) {
            $this->comments_style('error', 'Could not create the file in ' . $this->_fPath . '/' . $fileName . ' path', 'problem with file');
            $this->logProfiler('Could not create the file in ' . $this->_fPath . '/' . $fileName . ' path');
            return;
        }

        //Writing the field names as the header row in the export file.
        $header = "^" . implode("^" . $this->_fDel . "^", $fields) . "^" . "\r\n";
        $this->write_to_file($header, $fh);

        $chunksIds = array_chunk($productIds, $this->helper->getExportChunkSize());
        $pids = array();
        $finished = array();
        $process_limit = $this->helper->getExportProcessLimit();
        $count = 0;

        if (!$this->helper->getConfig('celexport/advanced/single_process')) {
            $processes =[];
            foreach ($chunksIds as $ids) {
                $count += 1;
                $i = $this->_fStore_id * 1000 + $count;
                if (count($processes) >= $process_limit) {
                    do {
                        sleep(1);
                        $state = true;
                        foreach ($processes as $key => $proc) {
                            if (!$proc->isRunning()) {
                                $state = false;
                                $finished[] = $key;
                                unset($processes[$key]);
                            }
                        }
                    } while ($state);
                }

                $this->logProfiler('Exported Products: ' . implode(',', $ids));
                $this->cache->save(
                    json_encode($ids),
                    'export_chunk_' . $this->_exportProcessId . '_' . $i,
                    [],
                    self::CACHE_LIFETIME
                );

                $comm = 'php ' . $this->_dir->getRoot() . '/bin/magento celebros:process ' . $i . ' ' . $this->_fStore_id . ' ' . $this->_exportProcessId;

                $process = new Process($comm);
                $processes[$i] = $process;
                $process->start();
                sleep(1);
            }

            do {
                foreach ($processes as $key => $proc) {
                    if (!$proc->isRunning()) {
                        $finished[] = $key;
                        unset($processes[$key]);
                    }
                }
                sleep(1);
            } while (count($processes));

            $_fPath = $this->helper->getExportPath($this->_exportProcessId) . '/' . $this->_fStore->getWebsite()->getCode() . '/' . $this->_fStore->getCode();
            if (!is_dir($_fPath)) {
                try {
                    $dir = mkdir($_fPath, 0777, true);
                } catch (\Exception $e) {
                    $this->comments_style('error', 'Could not create the directory in ' . $_fPath . ' path', 'problem with dir');
                    $this->logProfiler('Failed creating a directory at: '. $_fPath);
                    return;
                }
            }

            foreach ($finished as $key) {
                $status = $this->cache->load('process_' . $this->_exportProcessId . '_' . $key);

                if ($status == 'no_errors') {
                    $filePath = $_fPath . '/' . 'export_chunk_' . $key . "." . 'txt';
                    fwrite($fh, file_get_contents($filePath));
                    unlink($filePath);
                } else {
                    $this->comments_style('error', 'Exception from process: ' . $status, 'problem with process');
                    return;
                }

                $this->cache->remove('process_' . $this->_exportProcessId . '_' . $key);
            }

            fclose($fh);

            $this->cache->remove('export_custom_fields_' . $this->_exportProcessId);
        } else {
            $customAttributes = json_decode((string) $this->cache->load('export_custom_fields_' . $this->_exportProcessId));
            $exportHelper = $this->_objectManager->create('Celebros\Celexport\Helper\Export');
            foreach ($chunksIds as $ids) {
                $this->logProfiler('Exported Products: ' . implode(',', $ids));
                $str = $exportHelper->getProductsData($ids, $customAttributes, $store->getStoreId(), $this->_objectManager);
                fwrite($fh, (string) $str);
            }

            fclose($fh);
        }

        $this->logProfiler('Mem usage: ' . memory_get_usage(true));
        $this->logProfiler('Time usage: ' . $this->getTimeOffset(microtime(true)));
        $this->logProfiler("FINISH export products");
    }

    protected function _getCustomAttributes()
    {
        if ($this->helper->isCustomAttributesEnabled()) {
            $eav_attributes = $this->_resource->getTableName("eav_attribute");
            $catalog_eav_attribute = $this->_resource->getTableName("catalog_eav_attribute");
            $sql = "SELECT `attribute_code` FROM `{$eav_attributes}`
                LEFT JOIN `{$catalog_eav_attribute}` ON `{$catalog_eav_attribute}`.`attribute_id` = `{$eav_attributes}`.`attribute_id`
                WHERE `backend_model` IS NOT NULL
                    AND NOT `backend_model` = ''
                    AND `source_model` IS NOT NULL
                    AND NOT `source_model` = ''
                    AND (`is_searchable` = 1 OR `is_filterable` = 1)";
            $stm = $this->_resource->getConnection('read')->query($sql);
            return $stm->fetchAll();
        }

        return [];
    }

    public function ftpClose()
    {
        return ftp_close($this->_conn);
    }

    public function getTimeOffset($curentMicrotime)
    {
        $result = 0;

        if ($this->_timeMarker) {
            $result = round((float)$curentMicrotime - $this->_timeMarker, 3);
        }

        $this->_timeMarker = (float)$curentMicrotime;

        return $result;
    }
}
