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
namespace Celebros\Celexport\Controller\Adminhtml\Debug;

class Cron extends \Celebros\Celexport\Controller\Adminhtml\Debug
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }
        $this->_view->loadLayout();
        $this->_setActiveMenu('Celebros_Celexport::export_menu_cron_debug');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Celebros Cron Debug'));
        $this->_view->renderLayout();
    }
    
}