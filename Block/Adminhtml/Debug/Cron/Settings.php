<?php
/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Block\Adminhtml\Debug\Cron;

class Settings extends \Magento\Backend\Block\Widget\Form\Generic
{
    public $helper;

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
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $data = [
            'generate_schedules_every' => $this->helper->getConfig('system/cron/index/schedule_generate_every') . '/' . $this->helper->getConfig('system/cron/default/schedule_generate_every'),
            'schedule_ahead_for' => $this->helper->getConfig('system/cron/index/schedule_ahead_for') . '/' . $this->helper->getConfig('system/cron/default/schedule_ahead_for'),
            'schedule_lifetime'    => $this->helper->getConfig('system/cron/default/schedule_lifetime') . '/' . $this->helper->getConfig('system/cron/default/schedule_lifetime')
        ];

        $fieldset = $form->addFieldset('magento_cron_settings', ['legend' => __('Magento Cron Settings')]);

        $fieldset->addField('generate_schedules_every', 'label', [
            'name'     => 'generate_schedules_every',
            'label'    => __('Generate Schedules Every') . ' (' . __('index') . '/' . __('default') . ')',
            'title'    => __('Generate Schedules Every') . ' (' . __('index') . '/' . __('default') . ')',
            'required' => false,
            'disabled' => true
        ]);

        $fieldset->addField('schedule_ahead_for', 'label', [
            'name'     => 'schedule_ahead_for',
            'label'    => __('Schedule Ahead For') . ' (' . __('index') . '/' . __('default') . ')',
            'title'    => __('Schedule Ahead For') . ' (' . __('index') . '/' . __('default') . ')',
            'required' => false,
            'disabled' => true
        ]);

        $fieldset->addField('schedule_lifetime', 'label', [
            'name'     => 'schedule_lifetime',
            'label'    => __('Missed if Not Run Within') . ' (' . __('index') . '/' . __('default') . ')',
            'title'    => __('Missed if Not Run Within') . ' (' . __('index') . '/' . __('default') . ')',
            'required' => false,
            'disabled' => true
        ]);

        $form->setValues($data);
        $form->setMethod('post');
        $form->setUseContainer(false);
        $form->setId('magento_cron_settings');

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
