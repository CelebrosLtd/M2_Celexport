<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class ImagesResolution extends AbstractFieldArray
{
    /**
     * @var \Magento\Framework\View\Element\Html\Select
     */
    protected $imageTypeRenderer;

    /**
     * Image source model
     *
     * @var \Celebros\Celexport\Model\Config\Source\Images
     */
    private $images;

    /**
     * @param \Magento\Backend\Block\Template\Context $context,
     * @param \Celebros\Celexport\Model\Config\Source\Images $images
     * @param array $data
     * @return void
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Celebros\Celexport\Model\Config\Source\Images $images,
        array $data = []
    ) {
        $this->images = $images;
        parent::__construct($context, $data);
    }

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'type',
            [
                'label' => __('Image Type'),
                'class' => 'required-entry validate-no-empty',
                'renderer' => $this->getImageTypeRenderer()
            ]
        );
        $this->addColumn(
            'height',
            [
                'label' => __('Height'),
                'class' => 'required-entry validate-digits validate-not-negative-number'
            ]
        );
        $this->addColumn(
            'width',
            [
                'label' => __('Width'),
                'class' => 'required-entry validate-digits validate-not-negative-number'
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Image Type');
    }

    /**
     * Renderer for image type column
     *
     * @return \Magento\Framework\View\Element\Html\Select
     */
    protected function getImageTypeRenderer()
    {
        if (!$this->imageTypeRenderer) {
            $this->imageTypeRenderer = $this->getLayout()->createBlock(
                'Magento\Framework\View\Element\Html\Select',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );

            $this->imageTypeRenderer->setOptions(
                $this->images->excludeOption('original')->toOptionArray()
            );

            $this->imageTypeRenderer->setClass('image_type_select validate-options');
        }

        return $this->imageTypeRenderer;
    }


    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->getImageTypeRenderer()
            ->calcOptionHash($row->getData('type'))] =
            'selected="selected"';

        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new \Exception('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->_getCellInputElementName($columnName);

        if ($column['renderer']) {
            return $column['renderer']->setInputName(
                $inputName
            )->setName(
                $inputName
            )->setInputId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setColumnName(
                $columnName
            )->setColumn(
                $column
            )->toHtml();
        }

        return parent::renderCellTemplate($columnName);
    }
}
