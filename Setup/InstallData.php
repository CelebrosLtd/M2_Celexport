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

use Magento\Framework\Module\Setup\Migration;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $data = [
            'title' => 'title',
            'link'  => 'link',
            'status' => 'status',
            'image_link' => 'image_link',
            'thumbnail_label' => 'thumbnail_label',
            'rating' => 'rating',
            'short_description' => 'short_description',
            'mag_id' => 'mag_id',
            'visible' => 'visible',
            'store_id' => 'store_id',
            'is_in_stock' => 'is_in_stock',
            'sku' => 'sku',
            'category' => 'category',
            'websites' => 'websites',
            'news_from_date' => 'news_from_date',
            'news_to_date' => 'news_to_date'
        ];
        
        $id = 1;
        foreach ($data as $xml_field => $code_field) {
            $setup->getConnection()->insertForce(
                $setup->getTable('celebros_mapping'),
                ['id'=> $id, 'xml_field' => $xml_field, 'code_field' => $code_field]
            );
            $id++;
        }

        $setup->endSetup();
    }
    
}