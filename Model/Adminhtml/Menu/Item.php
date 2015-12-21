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
namespace Celebros\Celexport\Model\Adminhtml\Menu;

/**
 * Menu item. Should be used to create nested menu structures with \Magento\Backend\Model\Menu
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Item extends \Magento\Backend\Model\Menu\Item
{
    const CELEBROS_EXPORT_PATH = 'celexport/export';
    
    /**
     * Check whether item has javascript callback on click
     *
     * @return bool
     */
    public function hasClickCallback()
    {
        return (($this->getUrl() == '#') || strpos($this->getUrl(), self::CELEBROS_EXPORT_PATH));
    }

    
    /**
     * Retrieve item click callback
     *
     * @return string
     */
    public function getClickCallback()
    {
        if ($this->getUrl() == '#') {
            return 'return false;';
        } elseif (strpos($this->getUrl(), self::CELEBROS_EXPORT_PATH)) {
            return "window.open(this.href, '_blank');return false;";
        }
        return '';
    }

}