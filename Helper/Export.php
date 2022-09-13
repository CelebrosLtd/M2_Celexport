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
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\Url;
use Celebros\Celexport\Model\Exporter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\UrlRewrite\Model\UrlRewrite;
use Celebros\Celexport\Model\Config\Source\Images;
use Celebros\Celexport\Model\Config\Source\Prodparams;

class Export extends Data
{
    //public const MIN_MEMORY_LIMIT = 256;
    //public const MAX_EXEC_TIME = 18000;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $_objectManager;

    /**
     * @var int
     */
    protected $_storeId;

    /**
     * Default Images Resolution
     * @var Array
     */
    protected $defResolutions = [
        'image' => [
            'height' => 700,
            'width' => 700
        ],
        'small_image' => [
            'height' => 120,
            'width' => 120
        ],
        'thumbnail' => [
            'height' => 90,
            'width' => 90
        ]
    ];

    /**
     * @var Array
     */
    protected $resolutions = [];

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    public $jsonHelper;

    /**
     * @var Array
     */
    public $indexedPricesMapping = [
        GroupedType::TYPE_CODE => 'min_price',
        ProductType::TYPE_BUNDLE => 'min_price',
        ConfigurableType::TYPE_CODE => 'price'
    ];

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Code\Minifier\Adapter\Css\CSSmin $cssMin
     * @param \Magento\Framework\Code\Minifier\Adapter\Js\JShrink $jsMin
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\Store\Model\StoreManager $stores
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Code\Minifier\Adapter\Css\CSSmin $cssMin,
        \Magento\Framework\Code\Minifier\Adapter\Js\JShrink $jsMin,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Store\Model\StoreManager $stores,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Catalog\Model\View\Asset\ImageFactory $viewAssetImageFactory,
        \Magento\Catalog\Model\Product\Image\ParamsBuilder $imageParamsBuilder
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->viewAssetImageFactory = $viewAssetImageFactory;
        $this->imageParamsBuilder = $imageParamsBuilder;
        parent::__construct(
            $context,
            $cssMin,
            $jsMin,
            $response,
            $stores,
            $assetRepo,
            $dir,
            $filesystem,
            $resource,
            $resourceConfig
        );
    }

    /**
     * Change php settings for export process
     *
     * @return void
     */
    public function initExportProcessSettings()
    {
        if ($limit = (int)$this->getMemoryLimit()) {
            ini_set('memory_limit', $limit . 'M');
        }

        if ($execTime = (int)$this->getMaxExecutionTime()) {
            ini_set('max_execution_time', $execTime);
        }
    }

    /**
     * Get resolution according to image type
     *
     * @return void
     */
    protected function getResolutionByType(string $type): ?array
    {
        if (!array_key_exists($type, $this->resolutions)) {
            $resolutions = (array) $this->jsonHelper->jsonDecode(
                $this->getConfig(self::CONFIG_EXPORT_IMAGES_RESOLUTION)
            );
            foreach ($resolutions as $resolution) {
                if (isset($resolution['type'])) {
                    $this->resolutions[$resolution['type']] = [
                        'height' => $resolution['height'],
                        'width' => $resolution['width']
                    ];
                }
            }

            if (!array_key_exists($type, $this->resolutions)) {
                $this->resolutions[$type] = array_key_exists($type, $this->defResolutions)
                    ? $this->defResolutions[$type] : [];
            }
        }

        return $this->resolutions[$type];
    }

    /**
     * Return product image url
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $type
     * @return string
     */
    public function getProductImage($product, $type = null)
    {
        $bImageExists = 'no_errors';
        $url = null;
        try {
            $resolution = $this->getResolutionByType($type);
            if ($type && $type != 'original' && !empty($resolution)) {
                if ($product->getData($type) != 'no_selection') {
                    $viewImageConfig = [
                        "type" => $type,
                        "width" => $resolution['width'],
                        "height" => $resolution['height']
                    ];
                    $imageMiscParams = $this->imageParamsBuilder->build($viewImageConfig);
                    $originalFilePath = $product->getData($imageMiscParams['image_type']);
                    $imageAsset = $this->viewAssetImageFactory->create(
                        [
                            'miscParams' => $imageMiscParams,
                            'filePath' => $originalFilePath,
                        ]
                    );
                    $url = $imageAsset->getUrl();
                } else {
                    $url = $this->_objectManager->create(Image::class)
                        ->getDefaultPlaceholderUrl($type);
                }
            } else {
                $url = (string)$product->getMediaConfig()->getMediaUrl($product->getImage());
            }
        } catch (\Exception $e) {
            // We get here in case that there is no product image and no placeholder image is set.
            $bImageExists = false;
        }

        if (!$bImageExists || (stripos($url, 'no_selection') !== false) || (substr($url, -1) == '/')) {
            return null;
        }

        return $url;
    }

    /**
     * Return product price from magneto index
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getIndexedPrice($product)
    {
        $type = $product->getTypeId();
        $priceDataName = isset($this->indexedPricesMapping[$type]) ? $this->indexedPricesMapping[$type] : 'min_price';

        return $product->getData($priceDataName);
    }

    public function getCalculatedPrice($product)
    {
        $price = null;
        if ($this->useIndexedPrices()) {
            $price = $this->getIndexedPrice($product);
        }

        if (!$price) {
            if ($product->getData("type_id") == "bundle") {
                $product = $this->_objectManager->create(Product::class)
                    ->setStoreId($this->_storeId)
                    ->load($product->getEntityId());
                $priceModel  = $product->getPriceModel();
                $price = $priceModel->getTotalPrices($product, 'min', null, false);
            } elseif ($product->getData("type_id") == "grouped") {
                $product = $this->_objectManager->create(Product::class)
                    ->setStoreId($this->_storeId)
                    ->load($product->getEntityId());
                $aProductIds = $product->getTypeInstance()->getChildrenIds($product->getEntityId());
                $prices = [];
                foreach ($aProductIds as $ids) {
                    foreach ($ids as $id) {
                        $aProduct = $this->_objectManager->create(Product::class)->load($id);
                        if ($aProduct->isInStock()) {
                            $prices[] = $aProduct->getPriceModel()->getFinalPrice(null, $aProduct, true);
                        }
                    }
                }
                asort($prices);
                $price =  array_shift($prices);
            } elseif ($product->getData("type_id") == "giftcard") {
                $min_amount = PHP_INT_MAX;
                $product = $this->_objectManager->create(Product::class)->load($product->getId());
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
        } else {
            return $price;
        }

        if ($this->useCatalogPriceRules()) {
            $price = $this->_objectManager->create(Rule::class)
                ->calcProductPriceRule($product, $price);
        }

        return number_format($price, 2, ".", "");
    }

    private function getUrlInstance($storeId)
    {
        return $this->_objectManager->create(Url::class)->setScope($storeId);
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
            ->addFieldToFilter('entity_type', 'product')
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
        $websiteId = $this->_stores->getStore($storeId)->getWebsiteId();
        $this->_objectManager = $objectManager;
        $str = null;
        $this->setCurrentStore($this->_storeId);
        $urlBuilder = $this->getUrlInstance($this->_storeId);
        $entityName = $this->_objectManager->create(
            Exporter::class
        )->getProductEntityIdName("catalog_product_entity");
        $collection = $this->_objectManager->create(
            CollectionFactory::class
        )->create();
        $collection->setFlag('has_stock_status_filter', true);
        $collection->addFieldToFilter($entityName, ['in' => $ids])
            ->setStoreId($this->_storeId)
            ->addAttributeToSelect('visibility')
            ->addAttributeToSelect(['sku', 'price', 'image', 'small_image', 'thumbnail', 'type']);

        if (is_array($customAttributes) && !empty($customAttributes)) {
            $collection->addAttributeToSelect($customAttributes);
        }

        if ($this->useIndexedPrices()) {
            $collection->addPriceData();
        }

        $collection->addUrlRewrite()
            ->joinTable(
                ['items' => $this->_resource->getTableName('cataloginventory_stock_item')],
                'product_id = entity_id',
                ['manage_stock', 'is_in_stock', 'qty', 'min_sale_qty'],
                'stock_id = ' . \Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID . ' '
                    . \Zend_Db_Select::SQL_AND. ' items.website_id IN (' . implode(",", [0, $websiteId]) . ')',
                'left'
            );

        foreach ($collection as $product) {
            $routeParams = [
                '_direct' => $product->getRequestPath(),
                '_query' => [],
                '_nosid' => true
            ];

            $values = [
                "id"            => $product->getRowId() ? : $product->getEntityId(),
                "price"         => $this->getCalculatedPrice($product),
                "type_id"       => $product->getTypeId(),
                "product_sku"   => $product->getSku()
            ];

            if ($product->getRowId()) {
                $values["entity_id"] = $product->getEntityId();
            }

            $values["link"] = $this->getProductUrl(
                $product,
                $this->_storeId,
                $urlBuilder,
                $this->_objectManager->create(UrlRewrite::class)
            );

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
                    case 'regular_price':
                        $values['regular_price'] = $product->getPrice();
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
                    $values[$customAttribute] = ($product->getData($customAttribute) == "")
                        ? "" : trim(
                            (string)$product->getResource()->getAttribute($customAttribute)->getFrontend()->getValue(
                                $product
                            ),
                            " , "
                        );
                }
            }

            //Dispatching an event so that custom modules would be able to extend the functionality of the export,
            // by adding their own fields to the products export file.
            $this->_eventManager->dispatch('celexport_product_export', [
                'values'  => &$values,
                'product' => &$product,
            ]);

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
        $avTypes = (string)$this->getConfig(self::CONFIG_EXPORT_IMAGE_TYPES);
        $imageTypes = $objectManager->create(Images::class)->toOptionArray();
        foreach ($imageTypes as $key => $imageType) {
            if (!in_array($imageType['value'], explode(',', $avTypes))) {
                unset($imageTypes[$key]);
            }
        }

        return $imageTypes;
    }

    public function getProdParams($objectManager)
    {
        $avParams = (string)$this->getConfig('celexport/export_settings/product_parameters');
        $prodParams = $objectManager->create(Prodparams::class)->toOptionArray();
        foreach ($prodParams as $key => $prodParam) {
            if (!in_array($prodParam['value'], explode(',', $avParams))) {
                unset($prodParams[$key]);
            }
        }

        return $prodParams;
    }

    public function getMemoryLimit(): ?int
    {
        $limit = (int) $this->getConfig('celexport/advanced/memory_limit');

        return $limit ?: null;
    }

    public function getMaxExecutionTime(): ?int
    {
        $execTime = (int) $this->getConfig('celexport/advanced/max_execution_time');

        return $execTime ?: null;
    }
}
