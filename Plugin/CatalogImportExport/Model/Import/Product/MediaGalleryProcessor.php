<?php

namespace Cloudinary\Cloudinary\Plugin\CatalogImportExport\Model\Import\Product;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;

/**
 * Plugin for CatalogImportExport Uploader moduleList
 */
class MediaGalleryProcessor
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var SkuProcessor
     */
    private $skuProcessor;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * DB connection.
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var ResourceModelFactory
     */
    private $resourceFactory;

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel
     */
    private $resourceModel;

    /**
     * @var string
     */
    private $productEntityLinkField;

    /**
     * @var string
     */
    private $mediaGalleryTableName;

    /**
     * @var string
     */
    private $mediaGalleryValueTableName;

    /**
     * @var string
     */
    private $mediaGalleryValueVideoTableName;

    /**
     * @var string
     */
    private $mediaGalleryEntityToValueTableName;

    /**
     * @var string
     */
    private $productEntityTableName;

    /**
     * @method __construct
     * @param  Registry               $coreRegistry
     * @param  ConfigurationInterface $configuration
     * @param  SkuProcessor           $skuProcessor
     * @param  MetadataPool           $metadataPool
     * @param  ResourceConnection     $resourceConnection
     * @param  ResourceModelFactory   $resourceModelFactory
     */
    public function __construct(
        Registry $coreRegistry,
        ConfigurationInterface $configuration,
        SkuProcessor $skuProcessor,
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        ResourceModelFactory $resourceModelFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->configuration = $configuration;
        $this->skuProcessor = $skuProcessor;
        $this->metadataPool = $metadataPool;
        $this->connection = $resourceConnection->getConnection();
        $this->resourceFactory = $resourceModelFactory;
    }

    /**
     * Save product media gallery.
     *
     * @param \Magento\CatalogImportExport\Model\Import\Product\MediaGalleryProcessor $mediaGalleryProcessorModel
     * @param callable $proceed
     * @param array $mediaGalleryData
     * @return void
     */
    public function aroundSaveMediaGallery(\Magento\CatalogImportExport\Model\Import\Product\MediaGalleryProcessor $mediaGalleryProcessorModel, callable $proceed, array $mediaGalleryData)
    {
        $cloudinaryVideosImportMap = $this->coreRegistry->registry('cloudinary_videos_import_map') ?: [];

        if (!$this->configuration->isEnabled() || !$cloudinaryVideosImportMap) {
            return $proceed($mediaGalleryData);
        }

        $this->initMediaGalleryResources();
        $mediaGalleryDataGlobal = array_replace_recursive(...$mediaGalleryData);
        $imageNames = [];
        $multiInsertData = [];
        $valueToProductId = [];
        foreach ($mediaGalleryDataGlobal as $productSku => $mediaGalleryRows) {
            $productId = $this->skuProcessor->getNewSku($productSku)[$this->getProductEntityLinkField()];
            $insertedGalleryImgs = [];
            foreach ($mediaGalleryRows as $insertValue) {
                if (!in_array($insertValue['value'], $insertedGalleryImgs)) {
                    $valueArr = [
                        'attribute_id' => $insertValue['attribute_id'],
                        'value' => $insertValue['value'],
                        'media_type' => (isset($cloudinaryVideosImportMap[$insertValue['value']])) ? 'external-video' : 'image'
                    ];
                    $valueToProductId[$insertValue['value']][] = $productId;
                    $imageNames[] = $insertValue['value'];
                    $multiInsertData[] = $valueArr;
                    $insertedGalleryImgs[] = $insertValue['value'];
                }
            }
        }
        $oldMediaValues = $this->connection->fetchAssoc(
            $this->connection->select()->from($this->mediaGalleryTableName, ['value_id', 'value'])
                ->where('value IN (?)', $imageNames)
        );
        $this->connection->insertOnDuplicate($this->mediaGalleryTableName, $multiInsertData);
        $newMediaSelect = $this->connection->select()->from($this->mediaGalleryTableName, ['value_id', 'value'])
            ->where('value IN (?)', $imageNames);
        if (array_keys($oldMediaValues)) {
            $newMediaSelect->where('value_id NOT IN (?)', array_keys($oldMediaValues));
        }
        $newMediaValues = $this->connection->fetchAssoc($newMediaSelect);
        foreach ($mediaGalleryData as $storeId => $storeMediaGalleryData) {
            $this->processMediaPerStore((int)$storeId, $storeMediaGalleryData, $newMediaValues, $valueToProductId);
        }
    }

    /**
     * Init media gallery resources.
     *
     * @return void
     */
    private function initMediaGalleryResources()
    {
        if (null == $this->mediaGalleryTableName) {
            $this->productEntityTableName = $this->getResource()->getTable('catalog_product_entity');
            $this->mediaGalleryTableName = $this->getResource()->getTable('catalog_product_entity_media_gallery');
            $this->mediaGalleryValueTableName = $this->getResource()->getTable(
                'catalog_product_entity_media_gallery_value'
            );
            $this->mediaGalleryValueVideoTableName = $this->getResource()->getTable(
                'catalog_product_entity_media_gallery_value_video'
            );
            $this->mediaGalleryEntityToValueTableName = $this->getResource()->getTable(
                'catalog_product_entity_media_gallery_value_to_entity'
            );
        }
    }

    /**
     * Save media gallery data per store.
     *
     * @param int $storeId
     * @param array $mediaGalleryData
     * @param array $newMediaValues
     * @param array $valueToProductId
     * @return void
     */
    private function processMediaPerStore(
        int $storeId,
        array $mediaGalleryData,
        array $newMediaValues,
        array $valueToProductId
        ) {
        $multiInsertData = [];
        $multiInsertDataVideos = [];
        $dataForSkinnyTable = [];
        $cloudinaryVideosImportMap = $this->coreRegistry->registry('cloudinary_videos_import_map') ?: [];
        foreach ($mediaGalleryData as $mediaGalleryRows) {
            foreach ($mediaGalleryRows as $insertValue) {
                foreach ($newMediaValues as $value_id => $values) {
                    if ($values['value'] == $insertValue['value']) {
                        $insertValue['value_id'] = $value_id;
                        $insertValue[$this->getProductEntityLinkField()]
                                = array_shift($valueToProductId[$values['value']]);
                        unset($newMediaValues[$value_id]);
                        break;
                    }
                }
                if (isset($insertValue['value_id'])) {
                    $valueArr = [
                        'value_id' => $insertValue['value_id'],
                        'store_id' => $storeId,
                        $this->getProductEntityLinkField() => $insertValue[$this->getProductEntityLinkField()],
                        'label' => $insertValue['label'],
                        'position' => $insertValue['position'],
                        'disabled' => $insertValue['disabled'],
                    ];
                    $multiInsertData[] = $valueArr;
                    $dataForSkinnyTable[] = [
                        'value_id' => $insertValue['value_id'],
                        $this->getProductEntityLinkField() => $insertValue[$this->getProductEntityLinkField()],
                    ];
                    if (isset($cloudinaryVideosImportMap[$insertValue['value']])) {
                        $multiInsertDataVideos[$insertValue['value_id']] = [
                            'value_id' => $insertValue['value_id'],
                            'store_id' => $storeId,
                            'provider' => 'cloudinary',
                            'url'      => $cloudinaryVideosImportMap[$insertValue['value']]
                        ];
                    }
                }
            }
        }
        try {
            $this->connection->insertOnDuplicate(
                $this->mediaGalleryValueTableName,
                $multiInsertData,
                ['value_id', 'store_id', $this->getProductEntityLinkField(), 'label', 'position', 'disabled']
            );
            $this->connection->insertOnDuplicate(
                $this->mediaGalleryEntityToValueTableName,
                $dataForSkinnyTable,
                ['value_id']
            );
            if ($multiInsertDataVideos) {
                $this->connection->insertOnDuplicate(
                    $this->mediaGalleryValueVideoTableName,
                    $multiInsertDataVideos,
                    ['value_id', 'store_id']
                );
            }
            $this->coreRegistry->unregister('cloudinary_videos_import_map');
        } catch (\Exception $e) {
            $this->connection->delete(
                $this->mediaGalleryTableName,
                $this->connection->quoteInto('value_id IN (?)', $newMediaValues)
            );
            print_r($e->getMessage());
            die;
        }
    }

    /**
     * Get product entity link field.
     *
     * @return string
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        }

        return $this->productEntityLinkField;
    }

    /**
     * Get resource.
     *
     * @return \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel
     */
    private function getResource()
    {
        if (!$this->resourceModel) {
            $this->resourceModel = $this->resourceFactory->create();
        }

        return $this->resourceModel;
    }
}
