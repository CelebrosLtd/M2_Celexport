<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Celexport\Helper;

use Celebros\Celexport\Model\Config\Source\Images;
use Celebros\Celexport\Model\Config\Source\Prodparams;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\Stock;
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\DB\Select as DbSelect;
use Magento\Framework\Url;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

class Export extends Data
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Catalog\Model\View\Asset\ImageFactory
     */
    private $viewAssetImageFactory;

    /**
     * @var Product\Image\ParamsBuilder
     */
    private $imageParamsBuilder;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var Url
     */
    private $urlBuilder;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var UrlRewriteCollectionFactory
     */
    private $urlRewriteCollectionFactory;

    /**
     * @var Prodparams
     */
    private $prodparams;

    /**
     * @var Images
     */
    private $images;

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
     * @var Array
     */
    public $indexedPricesMapping = [
        GroupedType::TYPE_CODE => 'min_price',
        ProductType::TYPE_BUNDLE => 'min_price',
        ConfigurableType::TYPE_CODE => 'price'
    ];

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManager $stores
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Catalog\Model\View\Asset\ImageFactory $viewAssetImageFactory
     * @param Product\Image\ParamsBuilder $imageParamsBuilder
     * @param ImageHelper $imageHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param RuleFactory $ruleFactory
     * @param Url $urlBuilder
     * @param ProductCollectionFactory $productCollectionFactory
     * @param UrlRewriteCollectionFactory $urlRewriteCollectionFactory
     * @param Prodparams $prodparams
     * @param Images $images
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManager $stores,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Catalog\Model\View\Asset\ImageFactory $viewAssetImageFactory,
        \Magento\Catalog\Model\Product\Image\ParamsBuilder $imageParamsBuilder,
        ImageHelper $imageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        RuleFactory $ruleFactory,
        Url $urlBuilder,
        ProductCollectionFactory $productCollectionFactory,
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        Prodparams $prodparams,
        Images $images
    ) {
        parent::__construct(
            $context,
            $stores,
            $assetRepo,
            $directoryList,
            $filesystem,
            $resource,
            $resourceConfig
        );
        $this->jsonHelper = $jsonHelper;
        $this->viewAssetImageFactory = $viewAssetImageFactory;
        $this->imageParamsBuilder = $imageParamsBuilder;
        $this->imageHelper = $imageHelper;
        $this->productRepository = $productRepository;
        $this->ruleFactory = $ruleFactory;
        $this->urlBuilder = $urlBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->prodparams = $prodparams;
        $this->images = $images;
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
                    $url = $this->imageHelper->getDefaultPlaceholderUrl($type);
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
                $product = $this->productRepository->getById($product->getEntityId(), false, $this->_storeId);
                $priceModel  = $product->getPriceModel();
                $price = $priceModel->getTotalPrices($product, 'min', null, false);
            } elseif ($product->getData("type_id") == "grouped") {
                $product = $this->productRepository->getById($product->getEntityId(), false, $this->_storeId);
                $aProductIds = $product->getTypeInstance()->getChildrenIds($product->getEntityId());
                $prices = [];
                foreach ($aProductIds as $ids) {
                    foreach ($ids as $id) {
                        $aProduct = $this->productRepository->getById($id, false, $this->_storeId);
                        if ($aProduct->isInStock()) {
                            $prices[] = $aProduct->getPriceModel()->getFinalPrice(null, $aProduct, true);
                        }
                    }
                }
                asort($prices);
                $price =  array_shift($prices);
            } elseif ($product->getData("type_id") == "giftcard") {
                $min_amount = PHP_INT_MAX;
                $product = $this->productRepository->getById($product->getEntityId(), false, $this->_storeId);
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
            /** @var Rule $ruleModel */
            $ruleModel = $this->ruleFactory->create();
            $price = $ruleModel->calcProductPriceRule($product, $price);
        }

        return number_format($price, 2, ".", "");
    }

    protected function getProductUrl($product, $storeId, $urlBuilder)
    {
        $requestPath = false;
        if ($product->isVisibleInSiteVisibility()) {
            if (!$product->getRequestPath()) {
                $requestPath = $this->extractProductUrl($product, $storeId);
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

    protected function extractProductUrl($product, $storeId)
    {
        $requestPath = false;

        /** @var UrlRewriteCollection $urlRewriteCollection */
        $urlRewriteCollection = $this->urlRewriteCollectionFactory->create();
        $paths = $urlRewriteCollection
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

    public function getProductsData($ids, $customAttributes, $storeId)
    {
        $this->_storeId = $storeId;
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $str = '';
        $this->setCurrentStore($this->_storeId);
        $entityName = $this->getProductEntityIdName("catalog_product_entity");
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setFlag('has_stock_status_filter', true);
        $productCollection->addFieldToFilter($entityName, ['in' => $ids])
            ->setStoreId($this->_storeId)
            ->addAttributeToSelect('visibility')
            ->addAttributeToSelect(['sku', 'price', 'image', 'small_image', 'thumbnail', 'type']);

        if (is_array($customAttributes) && !empty($customAttributes)) {
            $productCollection->addAttributeToSelect($customAttributes);
        }

        if ($this->useIndexedPrices()) {
            $productCollection->addPriceData();
        }

        $productCollection->addUrlRewrite()
            ->joinTable(
                ['items' => $this->resource->getTableName('cataloginventory_stock_item')],
                'product_id = entity_id',
                ['manage_stock', 'is_in_stock', 'qty', 'min_sale_qty'],
                sprintf(
                    "stock_id = %s %s items.website_id IN (%s)",
                    Stock::DEFAULT_STOCK_ID,
                    DbSelect::SQL_AND,
                    implode(",", [0, $websiteId])
                ),
                'left'
            );

        foreach ($productCollection as $product) {
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
                $this->urlBuilder->setScope($storeId)
            );

            $prodParams = $this->getProdParams();
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

            $imageTypes = $this->getImageTypes();
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

    public function getImageTypes()
    {
        $avTypes = (string)$this->getConfig(self::CONFIG_EXPORT_IMAGE_TYPES);
        $imageTypes = $this->images->toOptionArray();
        foreach ($imageTypes as $key => $imageType) {
            if (!in_array($imageType['value'], explode(',', $avTypes))) {
                unset($imageTypes[$key]);
            }
        }

        return $imageTypes;
    }

    public function getProdParams()
    {
        $avParams = (string)$this->getConfig('celexport/export_settings/product_parameters');
        $prodParams = $this->prodparams->toOptionArray();
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

    public function getProductEntityIdName($tableName)
    {
        $entityIds = [
            'row_id',
            'entity_id'
        ];
        $table = $this->resource->getTableName($tableName);
        foreach ($entityIds as $entityId) {
            $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$entityId}'";
            if ($this->resource->getConnection('read')->fetchOne($sql)) {
                return $entityId;
            }
        }
        return false;
    }
}
