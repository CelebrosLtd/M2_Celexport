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

declare(strict_types=1);

namespace Celebros\Celexport\Setup\Patch\Data;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveRedundantTables implements DataPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @var array
     */
    protected $redundantTables = [
        'celebros_cache',
        'celebros_mapping'
    ];

    /**
     * @param SchemaSetupInterface $schemaSetup
     * @return void
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $installer = $this->schemaSetup;
        $installer->startSetup();

        foreach ($this->redundantTables as $table) {
            if ($installer->tableExists($table)) {
                $installer->getConnection()->dropTable(
                    $installer->getTable($table)
                );
            }
        }

        $installer->endSetup();
    }
}
