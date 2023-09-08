<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Controller\Adminhtml\Debug;

class Crongrid extends \Celebros\Celexport\Controller\Adminhtml\Debug
{
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
