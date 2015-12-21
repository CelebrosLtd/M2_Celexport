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
use Magento\Framework\App\Bootstrap;
use Magento\Store\Model\StoreManager;

require $argv[3];
$params = $_SERVER;
//$params[StoreManager::PARAM_RUN_CODE] = 'admin';
//$params[StoreManager::PARAM_RUN_TYPE] = 'store';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
/** @var \Celebros\Celexport\App\Export $app */
$app = $bootstrap->createApplication('Celebros\Celexport\App\Export', [
    'chunkId' => $argv[1],
    'storeId' => $argv[2],
    'processId' => $argv[4]
]);
$bootstrap->run($app);