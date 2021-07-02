<?php

/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 * @category    Celebros
 * @package     Celebros_Celexport
 */

namespace Celebros\Celexport\Model\Config\Source\Events;

class Cache implements \Magento\Framework\Option\ArrayInterface
{
    public const IMAGE_FLUSH_EVENT_NAME =  'images_flush';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::IMAGE_FLUSH_EVENT_NAME, 'label' => __('Flush Catalog Images Cache')],
        ];
    }
}
