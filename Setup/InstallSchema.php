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
namespace Celebros\Celexport\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        /**
         * Create table 'celebros_cache'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('celebros_cache')
        )->addColumn(
            'cache_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            NULL,
            ['identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'primary' => TRUE],
            'Cache Id'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Name'
        )->addColumn(
            'content',
            \Magento\Framework\DB\Ddl\Table::TYPE_BLOB,
            NULL,
            [],
            'Content'
        )->setComment(
            'Celebros Cache Table'
        );
        $installer->getConnection()->createTable($table);
        
        /**
         * Create table 'celebros_mapping'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('celebros_mapping')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            NULL,
            ['identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'primary' => TRUE],
            'Id'
        )->addColumn(
            'xml_field',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            256,
            [],
            'XML Field'
        )->addColumn(
            'code_field',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            256,
            [],
            'Code Field'
        )->setComment(
            'Celebros Mapping'
        );
        $installer->getConnection()->createTable($table);
        
        /**
         * Create table 'celebros_cronlog'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('celebros_cronlog')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            NULL,
            ['identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'primary' => TRUE],
            'Id'
        )->addColumn(
            'executed_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            NULL,
            [],
            'Executed At'
        )->addColumn(
            'event',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            256,
            [],
            'Event'
        )->setComment(
            'Celebros Cronlog'
        );
        $installer->getConnection()->createTable($table);
        
        $installer->endSetup();
        
    }
    
}