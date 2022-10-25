<?php

namespace Cloudinary\Cloudinary\Model\Api;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Model\Framework\File\Uploader;
use Cloudinary\Cloudinary\Model\MediaLibraryMap;
use Cloudinary\Cloudinary\Model\MediaLibraryMapFactory;
use Cloudinary\Cloudinary\Model\ProductGalleryApiQueueFactory;
use Cloudinary\Cloudinary\Model\ProductImageFinder;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Image\AdapterFactory as ImageAdapterFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\AllowedProtocols;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Magento\MediaStorage\Model\ResourceModel\File\Storage\File as FileUtility;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Design\Config\FileUploader\FileProcessor;

class ProductGalleryManagement implements \Cloudinary\Cloudinary\Api\ProductGalleryManagementInterface
{
    /**
     * @var array
     */
    private $parsedRemoteFileUrl = [];

    /**
     * @var string|null
     */
    private $cldUniqid;

    /**
     * @var MediaLibraryMap|null
     */
    private $mapped;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductMediaConfig
     */
    protected $mediaConfig;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var ImageAdapterFactory
     */
    protected $imageAdapter;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var FileUtility
     */
    protected $fileUtility;

    /**
     * @var FileProcessor
     */
    protected $fileProcessor;

    /**
     * AllowedProtocols validator
     *
     * @var AllowedProtocols
     */
    private $protocolValidator;

    /**
     * @var NotProtectedExtension
     */
    private $extensionValidator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductImageFinder
     */
    private $productImageFinder;

    /**
     * @var CloudinaryImageManager
     */
    private $cloudinaryImageManager;

    /**
     * @var ResourcesManagement
     */
    private $cloudinaryResourcesManagement;

    /**
     * @var TransformationFactory
     */
    private $transformationFactory;

    /**
     * @var Processor
     */
    private $mediaGalleryProcessor;

    /**
     * @var MediaLibraryMapFactory
     */
    private $mediaLibraryMapFactory;

    /**
     * @var ProductGalleryApiQueueFactory
     */
    private $productGalleryApiQueueFactory;

    /**
     * @var AppEmulation
     */
    private $appEmulation;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @method __construct
     * @param  ConfigurationInterface        $configuration
     * @param  Http                          $request
     * @param  JsonHelper                    $jsonHelper
     * @param  ProductRepositoryInterface    $productRepository
     * @param  ProductMediaConfig            $mediaConfig
     * @param  Filesystem                    $fileSystem
     * @param  ImageAdapterFactory           $imageAdapterFactory
     * @param  Curl                          $curl
     * @param  FileUtility                   $fileUtility
     * @param  FileProcessor                 $fileProcessor
     * @param  AllowedProtocols              $protocolValidator
     * @param  NotProtectedExtension         $extensionValidator
     * @param  StoreManagerInterface         $storeManager
     * @param  ProductImageFinder            $productImageFinder
     * @param  CloudinaryImageManager        $cloudinaryImageManager
     * @param  ResourcesManagement           $cloudinaryResourcesManagement
     * @param  TransformationFactory         $transformationFactory
     * @param  Processor                     $mediaGalleryProcessor
     * @param  MediaLibraryMapFactory        $mediaLibraryMapFactory
     * @param  ProductGalleryApiQueueFactory $productGalleryApiQueueFactory
     * @param  AppEmulation                  $appEmulation
     * @param  ResourceConnection            $resourceConnection
     */
    public function __construct(
        ConfigurationInterface $configuration,
        Http $request,
        JsonHelper $jsonHelper,
        ProductRepositoryInterface $productRepository,
        ProductMediaConfig $mediaConfig,
        Filesystem $fileSystem,
        ImageAdapterFactory $imageAdapterFactory,
        Curl $curl,
        FileUtility $fileUtility,
        FileProcessor $fileProcessor,
        AllowedProtocols $protocolValidator,
        NotProtectedExtension $extensionValidator,
        StoreManagerInterface $storeManager,
        ProductImageFinder $productImageFinder,
        CloudinaryImageManager $cloudinaryImageManager,
        ResourcesManagement $cloudinaryResourcesManagement,
        TransformationFactory $transformationFactory,
        Processor $mediaGalleryProcessor,
        MediaLibraryMapFactory $mediaLibraryMapFactory,
        ProductGalleryApiQueueFactory $productGalleryApiQueueFactory,
        AppEmulation $appEmulation,
        ResourceConnection $resourceConnection
    ) {
        $this->configuration = $configuration;
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->productRepository = $productRepository;
        $this->mediaConfig = $mediaConfig;
        $this->fileSystem = $fileSystem;
        $this->imageAdapter = $imageAdapterFactory->create();
        $this->curl = $curl;
        $this->fileUtility = $fileUtility;
        $this->fileProcessor = $fileProcessor;
        $this->extensionValidator = $extensionValidator;
        $this->protocolValidator = $protocolValidator;
        $this->storeManager = $storeManager;
        $this->productImageFinder = $productImageFinder;
        $this->cloudinaryImageManager = $cloudinaryImageManager;
        $this->cloudinaryResourcesManagement = $cloudinaryResourcesManagement;
        $this->transformationFactory = $transformationFactory;
        $this->mediaGalleryProcessor = $mediaGalleryProcessor;
        $this->mediaLibraryMapFactory = $mediaLibraryMapFactory;
        $this->productGalleryApiQueueFactory = $productGalleryApiQueueFactory;
        $this->appEmulation = $appEmulation;
        $this->resourceConnection = $resourceConnection;
    }


    /**
     * Remove Gallery Item
     * @param $product
     * @param $url
     * @param $removeAllGallery
     * @return int|void
     * @throws NoSuchEntityException .
     */
    private function removeGalleryItem($product, $url, $removeAllGallery)
    {
        $unlinked = 0;
        if ($product) {
            $mediaGalleryEntries = $product->getMediaGalleryEntries();

            if (is_array($mediaGalleryEntries)) {
                foreach ($mediaGalleryEntries as $key => $entry) {
                    $image = $entry->getFile();
                    $url = preg_replace('/\?.*/', '', $url);
                    $image = $this->storeManager->getStore()->getBaseUrl() . 'pub/media/catalog/product' . $image;
                    $basename = basename($image);
                    $ext = pathinfo($image, PATHINFO_EXTENSION);
                    // removing unique id from original filename
                    $pattern = '/^' . $this->configuration::CLD_UNIQID_PREFIX . '.*?_/';
                    $basename = preg_replace($pattern, '', $basename);
                    $filename = basename($url);
                    $filename = preg_replace($pattern, '', $filename);
                    // $filename = $url . '.' . $ext;

                    if ($basename == $filename || $removeAllGallery) {
                        unset($mediaGalleryEntries[$key]);
                        $this->mediaGalleryProcessor->removeImage($product, $image);
                        $unlinked++;
                    }
                }
            }
            if ($unlinked) {
                $product->setMediaGalleryEntries($mediaGalleryEntries);
                try {
                    $product = $this->productRepository->save($product);
                } catch (\Exception $e) {
                    $message = ['type' => 'error', 'message' => 'Falied Delete Image Error: ' . $e->getMessage() . ' line ' . $e->getLine()];
                }
            }
            return $unlinked;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeProductMedia($sku, $urls, $delete_all_gallery = 0)
    {
        $result = [
            "passed" => 0,
            "failed" => [
                "count" => 0,
                "urls" => []
            ]
        ];

        $urls = (array) $urls;

        try {
            $product = $this->productRepository->get($sku);
            $this->checkEnvHeader();
            $this->checkEnabled();

            foreach ($urls as $i => $url) {
                $unlinked =  $this->removeGalleryItem($product, $url, $delete_all_gallery);
            }

            $result["passed"] = $unlinked;
        } catch (\Exception $e) {
            $result["failed"]["count"]++;
            $result["error"] = $e->getMessage();
            $result["failed"]["public_id"][] = $url;
        }

        return $this->jsonHelper->jsonEncode($result);
    }

    /**
     * {@inheritdoc}
     */
    public function addProductMedia($sku, $urls)
    {
        $result = [
            "passed" => 0,
            "failed" => [
                "count" => 0,
                "urls" => []
            ]
        ];
        try {
            $this->checkEnvHeader();
            $this->checkEnabled();
            $urls = (array)$urls;
            foreach ($urls as $i => $url) {
                try {
                    $url = (array)$url;
                    $this->processOrQueue(
                        (isset($url["url"])) ? $url["url"] : null,
                        $sku,
                        (isset($url["publicId"])) ? $url["publicId"] : null,
                        (isset($url["roles"])) ? $url["roles"] : null,
                        (isset($url["label"])) ? $url["label"] : null,
                        (isset($url["disabled"])) ? $url["disabled"] : null,
                        (isset($url["cldspinset"])) ? $url["cldspinset"] : null
                    );
                    $result["passed"]++;
                } catch (\Exception $e) {
                    $result["failed"]["count"]++;
                    $url["error"] = $e->getMessage();
                    $result["failed"]["urls"][] = $url;
                }
            }
        } catch (\Exception $e) {
            $result["error"] = 1;
            $result["message"] = $e->getMessage();
        }

        if ($result["passed"] && !$result["failed"]["count"]) {
            $result["message"] = $this->configuration->isEnabledProductgalleryApiQueue() ? "All items have been added to queue." : "success";
        }

        return $this->jsonHelper->jsonEncode($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductMedia($sku)
    {
        return $this->_getProductMedia($sku);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsMedia($skus)
    {
        return $this->_getProductMedia($skus);
    }

    /**
     * [_getProductMedia description]
     * @method _getProductMedia
     * @param  mixed           $sku
     * @return string          (json result)
     */
    private function _getProductMedia($sku)
    {
        $result = ["data" => []];

        try {
            $this->checkEnvHeader();
            $this->checkEnabled();
            if (is_array($sku) || is_object($sku)) {
                foreach ($sku as $key => $_sku) {
                    $result['data'][$_sku] = $this->getProductCldUrlsBySku($_sku);
                }
            } else {
                $result['data'] = $this->getProductCldUrlsBySku($sku);
            }
        } catch (\Exception $e) {
            $result["error"] = 1;
            $result["message"] = $e->getMessage();
        }

        return $this->jsonHelper->jsonEncode($result);
    }

    /**
     * {@inheritdoc}
     */
    public function addItem($url = null, $sku = null, $publicId = null, $roles = null, $label = null, $disabled = 0, $cldspinset = null)
    {
        return $this->addItems([[
            "url" => $url,
            "sku" => $sku,
            "publicId" => $publicId,
            "roles" => $roles,
            "label" => $label,
            "disabled" => $disabled,
            "cldspinset" => $cldspinset
        ]]);
    }

    /**
     * {@inheritdoc}
     */
    public function addItems($items)
    {
        $result = [
            "errors" => 0,
            "items" => [],
            "message" => ""
        ];
        try {
            $this->checkEnvHeader();
            $this->checkEnabled();
            $items = (array)$items;
            foreach ($items as $i => $item) {
                try {
                    $item = $result["items"][$i] = (array)$item;
                    $result["items"][$i]["error"] = 0;
                    $result["items"][$i]["message"] = $this->configuration->isEnabledProductgalleryApiQueue() ? "The item was added to the queue." : "success";
                    $this->processOrQueue(
                        (isset($item["url"])) ? $item["url"] : null,
                        (isset($item["sku"])) ? $item["sku"] : null,
                        (isset($item["publicId"])) ? $item["publicId"] : null,
                        (isset($item["roles"])) ? $item["roles"] : null,
                        (isset($item["label"])) ? $item["label"] : null,
                        (isset($item["disabled"])) ? $item["disabled"] : null,
                        (isset($item["cldspinset"])) ? $item["cldspinset"] : null
                    );
                } catch (\Exception $e) {
                    $result["errors"]++;
                    $result["items"][$i]["error"] = 1;
                    $result["items"][$i]["message"] = $e->getMessage();
                    if ($this->mapped && $this->mapped->getId()) {
                        $this->mapped->delete();
                    }
                }
            }
        } catch (\Exception $e) {
            $result["errors"]++;
            $result["message"] = "\n{$e->getMessage()}";
        }

        if (!$result["errors"]) {
            $result["message"] = $this->configuration->isEnabledProductgalleryApiQueue() ? "All items have been added to queue." : "success";
        } else {
            $result["message"] = "error" . $result["message"];
        }

        return $this->jsonHelper->jsonEncode($result);
    }

    /**
     * @method processOrQueue
     * @param  string        $url
     * @param  string        $sku
     * @param  string|null   $publicId
     * @param  string|null   $roles
     * @param  string|null   $label
     * @param  bool|int|null $disabled
     * @param  string        $cldspinset
     */
    public function processOrQueue($url, $sku, $publicId = null, $roles = null, $label = null, $disabled = 0, $cldspinset = null)
    {
        if (!$url && !$cldspinset) {
            throw new LocalizedException(
                __("The `url` field is mandatory when not passing `cldspinset`.")
            );
        }
        if (!$sku) {
            throw new LocalizedException(
                __("The `sku` field is mandatory.")
            );
        }
        if ($this->configuration->isEnabledProductgalleryApiQueue()) {
            $fullItemData = $this->jsonHelper->jsonEncode([
                "url" => $url,
                "sku" => $sku,
                "publicId" => $publicId,
                "roles" => $roles,
                "label" => $label,
                "disabled" => $disabled,
                "cldspinset" => $cldspinset
            ]);
            return $this->productGalleryApiQueueFactory->create()
                ->setSku($sku)
                ->setFullItemData($fullItemData)
                ->save();
        } else {
            return $this->addGalleryItem($url, $sku, $publicId, $roles, $label, $disabled, $cldspinset);
        }
    }
    /**
     * @method addGalleryItem
     * @param  string        $url
     * @param  string        $sku
     * @param  string|null   $publicId
     * @param  string|null   $roles
     * @param  string|null   $label
     * @param  bool|int|null $disabled
     * @param  string        $cldspinset
     * @return $this
     */
    public function addGalleryItem($url, $sku, $publicId = null, $roles = null, $label = null, $disabled = 0, $cldspinset = null)
    {
        try {
            $this->emulateAdminhtmlArea();

            $this->cldUniqid = $this->mapped = null;

            if ($cldspinset) {
                $imageData = (array) $this->jsonHelper->jsonDecode($this->cloudinaryResourcesManagement->setId($cldspinset)->setMaxResults(1)->getResourcesByTag());
                if (!$imageData || $imageData["error"] || !$imageData["data"] || !$imageData["data"][0] || $imageData["data"][0]["resource_type"] !== "image") {
                    throw new LocalizedException(
                        __("No spin set exists for the given tag. Ensure you have uploaded it to Cloudinary correctly, or try again with a different tag name.")
                    );
                } else {
                    $imageData["data"] = (array) $imageData["data"][0];
                    $url = $url ?: $imageData["data"]["secure_url"];
                }
            }
            if (!$url) {
                throw new LocalizedException(
                    __("The `url` field is mandatory.")
                );
            }

            $this->parsedRemoteFileUrl = $this->configuration->parseCloudinaryUrl($url, $publicId);

            if (!$this->parsedRemoteFileUrl["version"] && !$publicId) {
                throw new LocalizedException(
                    __("The `publicId` field is mandatory for Cloudinary URLs that doesn't contain a version number.")
                );
            }

            $roles = ($roles) ? array_map('trim', (is_string($roles) ? explode(',', $roles) : (array) $roles)) : null;
            $product = $this->productRepository->get($sku);

            $result = $this->retrieveImage($this->parsedRemoteFileUrl['thumbnail_url'] ?: $this->parsedRemoteFileUrl['transformationless_url']);
            $result["file"] = $this->mediaGalleryProcessor->addImage(
                $product,
                $result["tmp_name"],
                $roles,
                true,
                false
            );

            $mediaGalleryData = $product->getMediaGallery();
            // last gallery value from array
            $galItem = array_pop($mediaGalleryData["images"]);

            if ($this->parsedRemoteFileUrl["type"] === "video") {
                $videoData = (array) $this->jsonHelper->jsonDecode($this->cloudinaryResourcesManagement->setId($this->parsedRemoteFileUrl["publicId"])->getVideo());
                $videoData["title"] = $label;
                $videoData["description"] = "";
                if (!$videoData["error"]) {
                    $videoData["context"] = new DataObject((isset($videoData["data"]["context"])) ? (array)$videoData["data"]["context"] : []);
                    $videoData["title"] = $videoData["title"] ? $videoData["title"] : ($videoData["context"]->getData('caption') ?: $videoData["context"]->getData('alt'));
                    $videoData["description"] = $videoData["context"]->getData('description') ?: $videoData["context"]->getData('alt');
                }
                $videoData["title"] = $videoData["title"] ?: $this->parsedRemoteFileUrl["publicId"];
                $videoData["description"] = preg_replace('/(&nbsp;|<([^>]+)>)/i', '', $videoData["description"] ?: $videoData["title"]);

                $galItem = array_merge($galItem, [
                    "media_type" => "external-video",
                    "video_provider" => "cloudinary",
                    "disabled" => $disabled ? 1 : 0,
                    "label" => $videoData["title"],
                    "video_url" => $this->parsedRemoteFileUrl["orig_url"],
                    "video_title" => $videoData["title"],
                    "video_description" => $videoData["description"],
                ]);
            }

            if ($this->parsedRemoteFileUrl["type"] === "image") {
                if (!$label) {
                    $imageData = $imageData ?: (array) $this->jsonHelper->jsonDecode($this->cloudinaryResourcesManagement->setId($this->parsedRemoteFileUrl["publicId"])->getImage());
                    if (!$imageData["error"]) {
                        $imageData["context"] = new DataObject((isset($imageData["data"]["context"])) ? (array)$imageData["data"]["context"] : []);
                        $label = $imageData["context"]->getData('caption') ?: $imageData["context"]->getData('alt');
                    }
                    $label = $label ?: "";
                }
                $galItem = array_merge($galItem, [
                    "disabled" => $disabled ? 1 : 0,
                    "label" => $label,
                    "cldspinset" => $cldspinset,
                ]);
            }

            $mediaGalleryData["images"][] = $galItem;
            $product->setData('media_gallery', $mediaGalleryData);

            $product->save();
            $mediaGalleryData = $product->getMediaGallery();
            $galItem = array_pop($mediaGalleryData["images"]);

            if ($this->parsedRemoteFileUrl["type"] === "image" && $this->parsedRemoteFileUrl['transformations_string']) {
                $this->transformationFactory->create()
                    ->setImageName($galItem["file"])
                    ->setFreeTransformation($this->parsedRemoteFileUrl['transformations_string'])
                    ->save();
            }

            if ($this->parsedRemoteFileUrl["type"] === "image" && $cldspinset) {
                $this->resourceConnection->getConnection()
                    ->insertOnDuplicate($this->resourceConnection->getTableName('cloudinary_product_spinset_map'), [
                        'image_name' => $galItem['file'],
                        'cldspinset' => $cldspinset
                    ], ['image_name', 'cldspinset']);
            }

            if ($this->configuration->isEnabledLocalMapping()) {
                $this->saveCloudinaryMapping();
            }
        } catch (\Exception $e) {
            $this->stopEnvironmentEmulation();
            throw $e;
        }

        return $this;
    }

    /**
     * @param string $remoteFileUrl
     * @return array
     */
    private function retrieveImage($remoteFileUrl)
    {
        try {
            $this->validateRemoteFile($remoteFileUrl);
            $baseTmpMediaPath = $this->mediaConfig->getBaseTmpMediaPath();
            if ($this->configuration->isEnabledLocalMapping()) {
                $this->cldUniqid = $this->configuration->generateCLDuniqid();
                $localUniqFilePath = $this->configuration->addUniquePrefixToBasename($remoteFileUrl, $this->cldUniqid);
            }
            $localUniqFilePath = $this->appendNewFileName($baseTmpMediaPath . $this->getLocalTmpFileName($localUniqFilePath));
            $this->validateRemoteFileExtensions($localUniqFilePath);
            $this->retrieveRemoteImage($remoteFileUrl, $localUniqFilePath);
            $localFileFullPath = $this->appendAbsoluteFileSystemPath($localUniqFilePath);
            $this->imageAdapter->validateUploadFile($localFileFullPath);
            $result = $this->appendResultSaveRemoteImage($localUniqFilePath, $baseTmpMediaPath);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
            $fileWriter = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA);
            if (isset($localFileFullPath) && $fileWriter->isExist($localFileFullPath)) {
                $fileWriter->delete($localFileFullPath);
            }
            throw $e;
        }
        return $result;
    }

    /**
     * @method checkEnvHeader
     * @return $this
     */
    private function checkEnvHeader()
    {
        if (($envVar = $this->request->getHeader('CLD-ENV-VAR'))) {
            $this->configuration->setRegistryEnabled(true);
            $this->configuration->setRegistryEnvVar($envVar);
        }
        return $this;
    }

    /**
     * @method checkEnabled
     * @return $this
     */
    private function checkEnabled()
    {
        if (!$this->configuration->isEnabled()) {
            throw new LocalizedException(
                __("Cloudinary module is disabled. Please enable it first in order to use this API.")
            );
        }
        return $this;
    }

    private function getLocalTmpFileName($remoteFileUrl)
    {
        $localFileName = Uploader::getCorrectFileName(basename($remoteFileUrl));
        return Uploader::getDispretionPath($localFileName) . DIRECTORY_SEPARATOR . $localFileName;
    }

    /**
     * Validate remote file
     *
     * @param string $remoteFileUrl
     * @throws LocalizedException
     *
     * @return $this
     */
    private function validateRemoteFile($remoteFileUrl)
    {
        if (!$this->protocolValidator->isValid($remoteFileUrl)) {
            throw new LocalizedException(
                __("Protocol isn't allowed")
            );
        }

        return $this;
    }

    /**
     * Invalidates files that have script extensions.
     *
     * @param string $filePath
     * @throws ValidatorException
     * @return void
     */
    private function validateRemoteFileExtensions($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!$this->extensionValidator->isValid($extension)) {
            throw new ValidatorException(__('Disallowed file type.'));
        }
    }

    /**
     * @param string $localUniqFilePath
     * @return mixed
     */
    private function appendResultSaveRemoteImage($localUniqFilePath, $baseTmpMediaPath)
    {
        $tmpFileName = $localUniqFilePath;
        if (substr($tmpFileName, 0, strlen($baseTmpMediaPath)) == $baseTmpMediaPath) {
            $tmpFileName = substr($tmpFileName, strlen($baseTmpMediaPath));
        }
        $result['name'] = basename($localUniqFilePath);
        $result['type'] = $this->imageAdapter->getMimeType();
        $result['error'] = 0;
        $result['size'] = filesize($this->appendAbsoluteFileSystemPath($localUniqFilePath));
        $result['url'] = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $localUniqFilePath;
        $result['tmp_name'] = $this->appendAbsoluteFileSystemPath($localUniqFilePath);
        $result['file'] = $tmpFileName;
        return $result;
    }

    /**
     * Trying to get remote image to save it locally
     *
     * @param string $fileUrl
     * @param string $localFilePath
     * @return void
     * @throws LocalizedException
     */
    private function retrieveRemoteImage($fileUrl, $localFilePath)
    {
        $this->curl->setConfig(['header' => false]);
        $this->curl->write('GET', $fileUrl);
        $image = $this->curl->read();
        if (empty($image)) {
            throw new LocalizedException(
                __('The preview image information is unavailable. Check your connection and try again.')
            );
        }
        $this->fileUtility->saveFile($localFilePath, $image);
    }

    /**
     * @param string $localFilePath
     * @return string
     */
    private function appendNewFileName($localFilePath)
    {
        $destinationFile = $this->appendAbsoluteFileSystemPath($localFilePath);
        $fileName = Uploader::getNewFileName($destinationFile);
        $fileInfo = pathinfo($localFilePath);
        return $fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @param string $localTmpFile
     * @return string
     */
    private function appendAbsoluteFileSystemPath($localTmpFile)
    {
        /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
        $mediaDirectory = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
        $pathToSave = $mediaDirectory->getAbsolutePath();
        return $pathToSave . $localTmpFile;
    }

    private function saveCloudinaryMapping()
    {
        return $this->mapped = $this->mediaLibraryMapFactory->create()
            ->setCldUniqid($this->cldUniqid)
            ->setCldPublicId(($this->parsedRemoteFileUrl["type"] === "video") ? $this->parsedRemoteFileUrl["thumbnail_url"] : $this->parsedRemoteFileUrl["publicId"] . '.' . $this->parsedRemoteFileUrl["extension"])
            ->setFreeTransformation($this->parsedRemoteFileUrl["transformations_string"])
            ->save();
    }

    /**
     * @method getProductMediaBySku
     * @param  string               $sku
     * @return array
     */
    private function getProductCldUrlsBySku($sku)
    {
        $urls = [
            'image' => null,
            'small_image' => null,
            'thumbnail' => null,
            'media_gallery' => [],
        ];
        try {
            $product = $this->productRepository->get($sku);
            foreach ($product->getMediaGalleryImages() as $gallItem) {
                $urls['media_gallery'][] = $gallItem->getUrl();
                if ($product->getData('image') === $gallItem->getFile()) {
                    $urls['image'] = $gallItem->getUrl();
                }
                if ($product->getData('small_image') === $gallItem->getFile()) {
                    $urls['small_image'] = $gallItem->getUrl();
                }
                if ($product->getData('thumbnail') === $gallItem->getFile()) {
                    $urls['thumbnail'] = $gallItem->getUrl();
                }
            }
        } catch (\Exception $e) {
            $urls = [
                'error' => 1,
                'message' => $e->getMessage(),
            ];
        }
        return $urls;
    }

    ///////////////////////////////
    // App Environment Emulation //
    ///////////////////////////////

    /**
     * Start environment emulation of the specified store
     *
     * Function returns information about initial store environment and emulates environment of another store
     *
     * @param  integer $storeId
     * @param  string  $area
     * @param  bool    $force   A true value will ensure that environment is always emulated, regardless of current store
     * @return $this
     */
    private function startEnvironmentEmulation($storeId, $area = Area::AREA_FRONTEND, $force = false)
    {
        $this->stopEnvironmentEmulation();
        $this->appEmulation->startEnvironmentEmulation($storeId, $area, $force);
        return $this;
    }

    /**
     * Stop environment emulation
     *
     * Function restores initial store environment
     *
     * @return $this
     */
    private function stopEnvironmentEmulation()
    {
        $this->appEmulation->stopEnvironmentEmulation();
        return $this;
    }

    /**
     * @method emulateAdminArea
     * @param  boolean          $force
     * @return $this
     */
    private function emulateAdminhtmlArea($force = true)
    {
        $this->startEnvironmentEmulation(0, Area::AREA_ADMINHTML, $force);
        return $this;
    }


}
