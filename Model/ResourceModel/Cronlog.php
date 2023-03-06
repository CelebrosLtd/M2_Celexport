<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Model\ResourceModel;

class Cronlog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
     /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('celebros_cronlog', 'id');
    }
}
