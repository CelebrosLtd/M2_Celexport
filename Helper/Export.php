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
                if ($product->getData($type) != 'no_selection') {
                    $url = $this->_objectManager->create('Magento\Catalog\Helper\Image')->init($product, $type)
                        ->setImageFile($product->getData($type))
                        ->resize($this->images[$type]['w'], $this->images[$type]['h'])
                        ->getUrl();
                } else {
                    $url = $this->_objectManager->create('Magento\Catalog\Helper\Image')->getDefaultPlaceholderUrl($type);
                }
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
    
    public function getIndexedPrice($product)
    {
        $type = $product->getTypeId();
        switch ($type) {
            case 'grouped':
                $price = $product->getMinPrice();
                break;
            case 'bundle':
                $price = $product->getMinPrice();
                break;
            default:
                $price = $product->getFinalPrice();
        }
        
        return $price; 
    }
    
    public function getCalculatedPrice($product)
    {
        $price = null;
        if ($this->useIndexedPrices()) {
            $price = $this->getIndexedPrice($product);
        }
        
        if (!$price) {
            if ($product->getData("type_id") == "bundle") {
                $product = $this->_objectManager->create('Magento\Catalog\Model\Product')
                    ->setStoreId($this->_storeId)
                    ->load($product->getEntityId());
                $priceModel  = $product->getPriceModel();
                $price = $priceModel->getTotalPrices($product, 'min', null, false);
            } elseif ($product->getData("type_id") == "grouped") {
                $product = $this->_objectManager->create('Magento\Catalog\Model\Product')
                    ->setStoreId($this->_storeId)
                    ->load($product->getEntityId());
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
                try {
                    $price = $product->getFinalPrice();
                } catch (\Exception $e) {
                    $price = 0;
                }
            }
        }
       
        return number_format($price, 2, ".", "");
    }
    
    private function getUrlInstance($storeId)
    {
        return $this->_objectManager->create('Magento\Framework\Url')->setScope($storeId);
    }
    
    public function getProductUrl($product, $storeId, $urlBuilder, $urlRewrite)
    {
        $requestPath = false;
        if ($product->isVisibleInSiteVisibility()) {
            if (!$product->getRequestPath()) {
                $requestPath = $this->extractProductUrl($product, $storeId, $urlRewrite);
            };
            
            $routeParams = [
                '_direct' => $requestPath ? : $product->getRequestPath(),
                '_query' => [],
                '_nosid' => true,
                '_seo_product_id' => 1
            ];
            
            return $urlBuilder->getUrl('', $routeParams);
        }
        
        return false;
    }
    
    public function extractProductUrl($product, $storeId, $urlRewrite)
    {
        $requestPath = false;
        $paths = $urlRewrite->getCollection()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('entity_id', $product->getEntityId())
            ->getColumnValues('request_path');
        if (!empty($paths)) {
            $lenghts = array_map('strlen', $paths);
            $requestPath = $paths[array_search(min($lenghts), $lenghts)];
        }
        
        return $requestPath;
    }
    
    public function getProductsData($ids, $customAttributes, $storeId, $objectManager)
    {
        $this->_storeId = $storeId;
        $this->_objectManager = $objectManager;
        $str = null;
        $this->setCurrentStore($this->_storeId);
        $urlBuilder = $this->getUrlInstance($this->_storeId);
        $entityName = $this->_objectManager->create('Celebros\Celexport\Model\Exporter')->getProductEntityIdName("catalog_product_entity");
        $collection = $this->_objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')->create();
        $collection->setFlag('has_stock_status_filter', true);
        $collection->addFieldToFilter($entityName, array('in' => $ids))
            ->setStoreId($this->_storeId)
            ->addAttributeToSelect('visibility')
            ->addAttributeToSelect(array('sku', 'price', 'image', 'small_image', 'thumbnail', 'type'));
        
        if (is_array($customAttributes) && !empty($customAttributes)) {
            $collection->addAttributeToSelect($customAttributes);
        }   
        
        if ($this->useIndexedPrices()) {    
            $collection->addPriceData();
        }
        
        $collection->addUrlRewrite()
            ->joinTable(
                $this->_resource->getTableName('cataloginventory_stock_item'),
                'product_id=entity_id',
                array('manage_stock', 'is_in_stock', 'qty', 'min_sale_qty'),
                null,
                'left'
            );
        
        foreach ($collection as $product) {
            $routeParams = [
                '_direct' => $product->getRequestPath(),
                '_query' => [],
                '_nosid' => true
            ];
            
            $values = array(
                "id"          => $product->getRowId() ? : $product->getEntityId(),
                "price"       => $this->getCalculatedPrice($product),
                "type_id"     => $product->getTypeId(),
                "product_sku" => $product->getSku()
            );
            
            if ($product->getRowId()) {
                $values["entity_id"] = $product->getEntityId();   
            }
                       
            $values["link"] = $this->getProductUrl($product, $this->_storeId, $urlBuilder, $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite'));
            
            $prodParams = $this->getProdParams($this->_objectManager);
            foreach ($prodParams as $prodParam) { 
                switch ($prodParam['value']) {
                    case 'is_saleable':
                        $values['is_salable'] = $product->isSaleable() ? "1" : "0";
                        break;
                    case 'manage_stock':
                        $values['manage_stock'] = $product->getManageStock() ? "1" : "0";
                        break;
                    case 'is_in_stock':
                        $values['is_in_stock'] = $product->getIsInStock() ? "1" : "0";
                        break;
                    case 'qty':
                        $values['qty'] = (int)$product->getQty();
                        break;
                    case 'min_qty':
                        $values['min_qty'] = (int)$product->getMinSaleQty();
                        break;
                    default:
                }                
            }
            
            $imageTypes = $this->getImageTypes($this->_objectManager);         
            foreach ($imageTypes as $imgType) {
                $values[(string)$imgType['label']] = $this->getProductImage($product, $imgType['value']);              
            }
            
            //Process custom attributes.
            if (is_array($customAttributes) && !empty($customAttributes)) {
                foreach ($customAttributes as $customAttribute) {
                    $values[$customAttribute] = ($product->getData($customAttribute) == "") ? "" : trim($product->getResource()->getAttribute($customAttribute)->getFrontend()->getValue($product), " , ");
                }
            }
            
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
        
        $this->setCurrentStore(0);
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
    
    public function getProdParams($objectManager)
    {
        $avParams = $this->getConfig('celexport/export_settings/product_parameters');
        $prodParams = $objectManager->create('Celebros\Celexport\Model\Config\Source\Prodparams')->toOptionArray();
        foreach ($prodParams as $key => $prodParam) {
            if (!in_array($prodParam['value'], explode(',', $avParams))) {
                unset($prodParams[$key]);
            }
        }
        
        return $prodParams;
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
