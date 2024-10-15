<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
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
     * @var \Celebros\Celexport\Model\Exporter
     */
    private $exporter;
    /**
     * @var \Celebros\Celexport\Helper\Data
     */
    private \Celebros\Celexport\Helper\Data $helper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Celebros\Celexport\Model\Exporter $exporter
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Celebros\Celexport\Model\Exporter $exporter,
        \Celebros\Celexport\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->exporter = $exporter;
        $this->helper = $helper;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if (!$this->helper->getConfig('celexport/advanced/single_process')) {
            $this->helper->comments_style('header', 0, 0);
            $this->helper->comments_style(
                'warning',
                'Exporting from the Web interface supports only Single Process Export. ' .
                'Please switch it on in Stores > Configuration > Celebros > Product Export > Advanced Settings'
            );
            $this->getResponse()->setBody($this->helper->getBodyForResponse());
            return;
        }

        $isWebRun = $this->getRequest()->getParam('webadmin');
        $this->getResponse()->setBody($this->exporter->export_celebros($isWebRun));
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
