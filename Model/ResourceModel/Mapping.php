<?php
/**
 * Celebros Qwiser - Magento Extension
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 * @category    Celebros
 * @package     Celebros_Celexport
 */
namespace Celebros\Celexport\Model\ResourceModel;

class Mapping extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
     /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('celebros_mapping', 'id');
    }
}
