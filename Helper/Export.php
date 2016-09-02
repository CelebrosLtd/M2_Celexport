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
namespace Celebros\Celexport\Helper;

use Magento\Framework\Stdlib\Datetime;

class Export extends Data
{
    const MIN_MEMORY_LIMIT = 256;
    protected $_storeId;
    protected $_objectManager;
    protected $_catalogProductMediaConfig;
    public $images = [
        'image' => [
            'h' => 700,
            'w' => 700
        ],
        'small_image' => [
            'h' => 120,
            'w' => 120
        ],
        'thumbnail' => [
            'h' => 90,
            'w' => 90
        ]
    ];
   
    public function getProductImage($product, $type = null)
    {
        $bImageExists = 'no_errors';
        $url = null;
        try {
            if ($type && $type != 'original' && isset($this->images[$type])) {
                $url = $this->_objectManager->create('Magento\Catalog\Helper\Image')->init($product, $type)
                    ->setImageFile($product->getData($type))
                    ->resize($this->images[$type]['w'], $this->images[$type]['h'])
                    ->getUrl();
            } else {
                $url = (string)$product->getMediaConfig()->getMediaUrl($product->getImage());
            }
        } catch (\Exception $e) {
            // We get here in case that there is no product image and no placeholder image is set.
            $bImageExists = false;
        }
        
        if (!$bImageExists || (stripos($url, 'no_selection') !== false) || (substr($url, -1) == '/')) {
            //$this->logProfiler('Warning: '. $type . ' Error: Product ID: '. $product->getEntityId() . ', image url: ' . $url, NULL);
            return null;
        }
     
        return $url;
    }
    
    public function getCalculatedPrice($product)
    {
        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')
            ->setStoreId($this->_storeId)
            ->load($product->getEntityId());
        $price = "";
        
        if ($product->getData("type_id") == "bundle") {
            $priceModel  = $product->getPriceModel();
            $price = $priceModel->getTotalPrices($product, 'min', null, false);
        } elseif ($product->getData("type_id") == "grouped") {
            $aProductIds = $product->getTypeInstance()->getChildrenIds($product->getEntityId());
            $prices = array();
            foreach ($aProductIds as $ids) {
                foreach ($ids as $id) {
                    $aProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($id);
                    if ($aProduct->isInStock()) {
                        $prices[] = $aProduct->getPriceModel()->getFinalPrice(null, $aProduct, true);
                    }
                }
            }
            asort($prices);
            $price =  array_shift($prices);
        } elseif ($product->getData("type_id") == "giftcard") {
            $min_amount = PHP_INT_MAX;
            $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
            if ($product->getData("open_amount_min") != null && $product->getData("allow_open_amount")) {
                $min_amount = $product->getData("open_amount_min");
            }
            foreach ($product->getData("giftcard_amounts") as $amount) {
                if ($min_amount > $amount["value"]) {
                    $min_amount = $amount["value"];
                }
            }
            $price =  $min_amount;
        } else {
            $price = $product->getFinalPrice();
        }
       
        return number_format($price, 2, ".", "");
    }
    
    public function getProductsData($ids, $customAttributes, $storeId, $objectManager)
    {
        $this->_storeId = $storeId;
        $this->_objectManager = $objectManager;
        $str = null;
        $this->setCurrentStore(0);
        $collection = $this->_objectManager->create('Magento\Catalog\Model\Product')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $ids))
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addAttributeToSelect(array('sku', 'price', 'image', 'small_image', 'thumbnail', 'type', 'is_salable'))
            ->addAttributeToSelect($customAttributes)
            ->joinTable(
                $this->_resource->getTableName('cataloginventory_stock_item'),
                'product_id=entity_id',
                array('manage_stock', 'is_in_stock', 'qty', 'min_sale_qty'),
                null,
                'left'
            )->load();
        
        $this->setCurrentStore($this->_storeId);
        foreach ($collection as $product) {
            $values = array(
                "id"                          => $product->getEntityId(),
                "price"                       => $this->getCalculatedPrice($product),
                "type_id"                     => $product->getTypeId(),
                "product_sku"                 => $product->getSku(),
                "is_salable"                  => $product->isSaleable() ? "1" : "0",
                "manage_stock"                => $product->getManageStock() ? "1" : "0",
                "is_in_stock"                 => $product->getIsInStock() ? "1" : "0",
                "qty"                         => (int)$product->getQty(),
                "min_qty"                     => (int)$product->getMinSaleQty(),
                "link"                        => $product->getProductUrl()
            );

            $imageTypes = $this->getImageTypes($this->_objectManager);         
            foreach ($imageTypes as $imgType) {
                $values[(string)$imgType['label']] = $this->getProductImage($product, $imgType['value']);              
            }
            
            //Process custom attributes.
            /*foreach ($customAttributes as $customAttribute) {
                $values[$customAttribute] = ($product->getData($customAttribute) == "") ? "" : trim($product->getResource()->getAttribute($customAttribute)->getFrontend()->getValue($product), " , ");
            }*/
            
            //Dispatching an event so that custom modules would be able to extend the functionality of the export,
            // by adding their own fields to the products export file.
            $this->_eventManager->dispatch('celexport_product_export', array(
                'values'  => &$values,
                'product' => &$product,
            ));
            
            $fDel = $this->getConfig('celexport/export_settings/delimiter');
            if ($fDel === '\t') {
                $fDel = chr(9);
            }
            
            $str .= "^" . implode("^" . $fDel . "^", $values) . "^" . "\r\n";
        }
        
        return $str;
    }
    
    public function getImageTypes($objectManager)
    {
        $avTypes = $this->getConfig('celexport/image_settings/image_types');
        $imageTypes = $objectManager->create('Celebros\Celexport\Model\Config\Source\Images')->toOptionArray();
        foreach ($imageTypes as $key => $imageType) {
            if (!in_array($imageType['value'], explode(',', $avTypes))) {
                unset($imageTypes[$key]);
            }
        }
        
        return $imageTypes;
    }
    
    public function getMemoryLimit()
    {
        $limit = (int) $this->getConfig('celexport/advanced/memory_limit');
        if (!$limit
        || $limit < self::MIN_MEMORY_LIMIT) {
            return self::MIN_MEMORY_LIMIT;
        }
        
        return $limit;
    }
}
