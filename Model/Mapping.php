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
namespace Celebros\Celexport\Model;

class Mapping extends \Magento\Framework\Model\AbstractModel
{
    protected $_mapping;
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Celebros\Celexport\Model\Resource\Mapping');
        $this->_mapping = $this->prepareMapping();
    }
    
    protected function prepareMapping()
    {
        $collection = $this->getCollection();
        foreach ($collection->getData() as $item) { 
            $this->_mapping[$item['xml_field']] = $item['code_field'];
        }
    }
    
    public function getMapping($field = NULL)
    {
        if ($field) {
            if (isset($this->_mapping[$field])) {
                return $this->_mapping[$field];
            } else {
                return $field;
            }
        }
        
        return $this->_mapping;
    }
    
    public function testm()
    {
        return 'sdfgsdfssdfhsdf';
        
    }
}