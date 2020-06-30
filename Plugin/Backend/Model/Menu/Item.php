<?php
/*
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
namespace Celebros\Celexport\Plugin\Backend\Model\Menu;

use Magento\Backend\Model\Menu\Item as BackendItem;

class Item
{
    const CELEBROS_EXPORT_PATH = 'celexport/export/manual';
    
    public function afterHasClickCallback(
        BackendItem $item,
        $return
    ) {
        return ($return || $this->isCelebrosExportPath($item));
    }
    
    public function afterGetClickCallback(
        BackendItem $item,
        $return
    ) {
        if ($this->isCelebrosExportPath($item)) {
            return "window.open(this.href, '_blank');return false;";
        }     

        return $return;
    }

    protected function isCelebrosExportPath(BackendItem $item) : bool
    {
        return (strpos($item->getUrl(), self::CELEBROS_EXPORT_PATH) !== false) ? true : false;
    }
}
