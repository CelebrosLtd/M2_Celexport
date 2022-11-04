<?php
/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Controller\Adminhtml\Export;

class Manual extends \Celebros\Celexport\Controller\Adminhtml\Export
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $model = $this->_objectManager->create('Celebros\Celexport\Model\Exporter');

        $isWebRun = $this->getRequest()->getParam('webadmin');
        $this->getResponse()->setBody($model->export_celebros($this->_objectManager, $isWebRun));
    }

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Celebros_Celexport::export_menu_manual_export');
    }
}
