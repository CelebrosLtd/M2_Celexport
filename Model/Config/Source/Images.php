<?php
/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Model\Config\Source;

class Images implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $exclOptions = [];

    /**
     * Add option to exclude
     *
     * @param string $option
     * @return \Celebros\Celexport\Model\Config\Source\Images
     */
    public function excludeOption(string $option)
    {
        $this->exclOptions[] = $option;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $options = [
            'original' => __('original_product_image_link'),
            'image' => __('image_link'),
            'small_image' => __('small_image'),
            'thumbnail' => __('thumbnail')
        ];

        foreach ($this->exclOptions as $opt) {
            if (array_key_exists($opt, $options)) {
                unset($options[$opt]);
            }
        }

        return $options;
    }


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array = $this->toArray();
        $options = array_map(
            function ($value, $label) { return ['value' => $value, 'label' => $label]; },
            array_keys($array),
            $array);

        return $options;
    }
}
