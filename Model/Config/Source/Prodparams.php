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
namespace Celebros\Celexport\Model\Config\Source;

class Prodparams implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'is_saleable', 'label' => __('is_saleable')],
            ['value' => 'manage_stock', 'label'  => __('manage_stock')],
            ['value' => 'is_in_stock', 'label'  => __('is_in_stock')],
            ['value' => 'qty', 'label'  => __('qty')],
            ['value' => 'min_qty', 'label'  => __('min_qty')],
            ['value' => 'regular_price', 'label'  => __('regular_price')],
        ];
    }
}
