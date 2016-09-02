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

class Images implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'original', 'label' => __('original_product_image_link')],
            ['value' => 'image', 'label'  => __('image_link')],
            ['value' => 'small_image', 'label'  => __('small_image')],
            ['value' => 'thumbnail', 'label'  => __('thumbnail')],
        ];
    }
}
