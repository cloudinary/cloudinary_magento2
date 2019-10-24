<?php

namespace Cloudinary\Cloudinary\Model\Api;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Model\ProductImageFinder;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Image\AdapterFactory as ImageAdapterFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\AllowedProtocols;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Magento\MediaStorage\Model\ResourceModel\File\Storage\File as FileUtility;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Design\Config\FileUploader\FileProcessor;

class ProductGalleryManagement implements \Cloudinary\Cloudinary\Api\ProductGalleryManagementInterface
{

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
     * @method __construct
     * @param  ConfigurationInterface     $configuration
     * @param  Http                       $request
     * @param  JsonHelper                 $jsonHelper
     * @param  ProductRepositoryInterface $productRepository
     * @param  ProductMediaConfig         $mediaConfig
     * @param  Filesystem                 $fileSystem
     * @param  ImageAdapterFactory        $imageAdapterFactory
     * @param  Curl                       $curl
     * @param  FileUtility                $fileUtility
     * @param  FileProcessor              $fileProcessor
     * @param  AllowedProtocols           $protocolValidator
     * @param  NotProtectedExtension      $extensionValidator
     * @param  StoreManagerInterface      $storeManager
     * @param  ProductImageFinder         $productImageFinder
     * @param  CloudinaryImageManager     $cloudinaryImageManager
     * @param  ResourcesManagement        $cloudinaryResourcesManagement
     * @param  TransformationFactory      $transformationFactory
     * @param  Processor                  $mediaGalleryProcessor
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
        Processor $mediaGalleryProcessor
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
    }

    /**
     * {@inheritdoc}
     */
    public function addItem($url, $sku, $publicId = null, $roles = null)
    {
        return $this->addItems([[
            "url" => $url,
            "sku" => $sku,
            "publicId" => $publicId,
            "roles" => $roles
        ]]);
    }

    /**
     * {@inheritdoc}
     */
    public function addItems($items)
    {
        $result = [
            "errors" => 0,
            "message" => ""
        ];
        try {
            if (!$this->configuration->isEnabled()) {
                throw new LocalizedException(
                    __("Cloudinary module is disabled. Please enable it first in order to use this API.")
                );
            }
            $items = (array)$items;
            foreach ($items as $item) {
                try {
                    $item = new DataObject($item);
                    $this->addGalleryItem(
                        $item->getData('url'),
                        $item->getData('sku'),
                        $item->getData('publicId'),
                        $item->getData('roles')
                    );
                } catch (\Exception $e) {
                    $result["errors"]++;
                    $result["message"] .= "addGalleryItem({$item->getData('url')}, {$item->getData('sku')}): {$e->getMessage()} \n";
                }
            }
        } catch (\Exception $e) {
            $result["errors"]++;
            $result["message"] .= "{$e->getMessage()} \n";
        }

        if (!$result["errors"]) {
            $result["message"] = "success";
        }

        return $this->jsonHelper->jsonEncode($result);
    }

    /**
     * @method addGalleryItem
     * @param  string       $url
     * @param  string       $sku
     * @param  string|null  $publicId
     * @param  string|null  $roles
     */
    private function addGalleryItem($url, $sku, $publicId = null, $roles = null)
    {
        $parsed = $this->configuration->parseCloudinaryUrl($url, $publicId);
        $roles = ($roles) ? array_map('trim', explode(',', $roles)) : null;
        $product = $this->productRepository->get($sku);

        $result = $this->retrieveImage($parsed['thumbnail_url'] ?: $parsed['transformationless_url']);
        $result["file"] = $this->mediaGalleryProcessor->addImage(
            $product,
            $result["tmp_name"],
            $roles,
            true,
            false
        );

        $mediaGalleryData = $product->getMediaGallery();
        $galItem = array_pop($mediaGalleryData["images"]);

        if ($parsed["type"] === "video") {
            $videoData = (array) $this->jsonHelper->jsonDecode($this->cloudinaryResourcesManagement->setId($parsed["publicId"])->getVideo());
            if (!$videoData["error"]) {
                $videoData["context"] = new DataObject((isset($videoData["data"]["context"])) ? (array)$videoData["data"]["context"] : []);
                $videoData["title"] = $videoData["context"]->getData('caption') ?: $videoData["context"]->getData('alt');
                $videoData["description"] = $videoData["context"]->getData('description') ?: $videoData["context"]->getData('alt');
            }
            $videoData["title"] = $videoData["title"] ?: $parsed["publicId"];
            $videoData["description"] = preg_replace('/(&nbsp;|<([^>]+)>)/i', '', $videoData["description"] ?: $videoData["title"]);

            $galItem = array_merge($galItem, [
                "media_type" => "external-video",
                "video_provider" => "cloudinary",
                "disabled" => 0,
                "video_url" => $parsed["orig_url"],
                "video_title" => $videoData["title"],
                "video_description" => $videoData["description"],
            ]);
            $mediaGalleryData["images"][] = $galItem;
            $product->setData('media_gallery', $mediaGalleryData);
        }

        $product->save();
        $mediaGalleryData = $product->getMediaGallery();
        $galItem = array_pop($mediaGalleryData["images"]);

        foreach ($this->productImageFinder->findNewImages($product) as $image) {
            $this->cloudinaryImageManager->uploadAndSynchronise($image);
        }

        if ($parsed["type"] === "image" && $parsed['transformations_string']) {
            $this->transformationFactory->create()
                ->setImageName($galItem["file"])
                ->setFreeTransformation($parsed['transformations_string'])
                ->save();
        }
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
            $localUniqFilePath = $this->appendNewFileName($baseTmpMediaPath . $this->getLocalTmpFileName($remoteFileUrl));
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

    private function getLocalTmpFileName($remoteFileUrl)
    {
        $localFileName = Uploader::getCorrectFileName(basename($remoteFileUrl));
        return Uploader::getDispersionPath($localFileName) . DIRECTORY_SEPARATOR . $localFileName;
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
}
