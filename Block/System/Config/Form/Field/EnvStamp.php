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
namespace Celebros\Celexport\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Setup\ModuleContextInterface;

class EnvStamp extends \Magento\Config\Block\System\Config\Form\Field
{
    const MODULE_NAME = 'Celebros_Celexport';
    protected $helper;
    
    public function __construct(
        \Celebros\Celexport\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }
    
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $id = $element->getHtmlId();
        $notice = ($this->getEnvStamp() != $this->helper->getConfiguratedEnvStamp()) ? '<p class="note" style="color:red;">' . __('configured env stamp is incorrect - ftp upload is disabled') . '</p>' : '';
        $html = '<tr id="row_' . $id . '">';
        $html .= '<td class="label">' . __('Current Env Stamp') . '</td><td class="value">' . $this->getEnvStamp() . $notice . '</td><td class="scope-label"></td>';
        $html .= '</tr>';
       
        return $html;
    }
    
    public function getEnvStamp()
    {
        return $this->helper->getCurrentEnvStamp();
    }
}
