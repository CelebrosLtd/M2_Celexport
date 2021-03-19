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

namespace Celebros\Celexport\Block\Adminhtml\Debug\Cron;

class System extends \Magento\Backend\Block\Widget\Form\Generic
{
    public $helper;
    public $timeZone;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Celebros\Celexport\Helper\Export $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Celebros\Celexport\Helper\Export $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->timeZone = $context->getLocaleDate();
        parent::__construct($context, $registry, $formFactory, $data);
    }
    
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $timeValue = $this->timeZone->date()->format('Y-m-d H:i:s') .
            ' / ' . $this->timeZone->date()->setTimeZone(
                new \DateTimeZone($this->timeZone->getDefaultTimezone())
            )->format('Y-m-d H:i:s');
        $data = [
            'current_magento_time'     => $timeValue,
            'celebros_cron_expression' => $this->helper->getConfig('celexport/export_settings/cron_expr'),
            'celebros_cron_enabled'    => $this->helper->getConfig('celexport/export_settings/cron_enabled') ? __('Yes') : __('No')
        ];
        
        $fieldset = $form->addFieldset('celebros_cron_settings', ['legend' => __('Celebros Cron Settings')]);
        
        $fieldset->addField('current_magento_time', 'label', [
            'name'     => 'current_magento_time',
            'label'    => __('Current Magento / UTC Time'),
            'title'    => __('Current Magento / UTC Time'),
            'required' => false,
            'disabled' => true
        ]);
        
        $fieldset->addField('celebros_cron_expression', 'label', [
            'name'     => 'celebros_cron_expression',
            'label'    => __('Celebros Cron Expression'),
            'title'    => __('Celebros Cron Expression'),
            'required' => false,
            'disabled' => true
        ]);
        
        $fieldset->addField('celebros_cron_enabled', 'label', [
            'name'     => 'celebros_cron_enabled',
            'label'    => __('Enable Cron Catalog Update'),
            'title'    => __('Enable Cron Catalog Update'),
            'required' => false,
            'disabled' => true
        ]);
        
        $form->setValues($data);
        $form->setMethod('post');
        $form->setUseContainer(false);
        $form->setId('celebros_cron_settings');
        
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}
