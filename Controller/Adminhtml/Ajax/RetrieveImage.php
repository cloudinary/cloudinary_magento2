<?php

namespace Cloudinary\Cloudinary\Controller\Adminhtml\Ajax;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Model\Framework\File\Uploader;
use Cloudinary\Cloudinary\Model\MediaLibraryMapFactory;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Image\AdapterFactory as ImageAdapterFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\AllowedProtocols;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Magento\MediaStorage\Model\ResourceModel\File\Storage\File as FileUtility;
use Magento\PageBuilder\Controller\Adminhtml\ContentType\Image\Upload as PageBuilderContentTypeUpload;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Design\Config\FileUploader\FileProcessor;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RetrieveImage extends \Magento\Backend\App\Action
{
    /**
     * @var string|null
     */
    private $remoteFileUrl;

    /**
     * @var bool
     */
    private $usingPlaceholderFallback = false;

    /**
     * @var array
     */
    private $parsedRemoteFileUrl = [];

    /**
     * @var string|null
     */
    private $cldUniqid;

    /**
     * @var ResultRawFactory
     */
    protected $resultRawFactory;

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
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var MediaLibraryMapFactory
     */
    private $mediaLibraryMapFactory;

    private $assetRepository;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  ResultRawFactory       $resultRawFactory
     * @param  ProductMediaConfig     $mediaConfig
     * @param  Filesystem             $fileSystem
     * @param  ImageAdapterFactory    $imageAdapterFactory
     * @param  Curl                   $curl
     * @param  FileUtility            $fileUtility
     * @param  FileProcessor          $fileProcessor
     * @param  AllowedProtocols       $protocolValidator
     * @param  NotProtectedExtension  $extensionValidator
     * @param  StoreManagerInterface  $storeManager
     * @param  ConfigurationInterface $configuration
     * @param  MediaLibraryMapFactory $mediaLibraryMapFactory
     */
    public function __construct(
        Context $context,
        ResultRawFactory $resultRawFactory,
        ProductMediaConfig $mediaConfig,
        Filesystem $fileSystem,
        ImageAdapterFactory $imageAdapterFactory,
        Curl $curl,
        FileUtility $fileUtility,
        FileProcessor $fileProcessor,
        AllowedProtocols $protocolValidator,
        NotProtectedExtension $extensionValidator,
        StoreManagerInterface $storeManager,
        ConfigurationInterface $configuration,
        MediaLibraryMapFactory $mediaLibraryMapFactory,
        AssetRepository $assetRepository
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->mediaConfig = $mediaConfig;
        $this->fileSystem = $fileSystem;
        $this->imageAdapter = $imageAdapterFactory->create();
        $this->curl = $curl;
        $this->fileUtility = $fileUtility;
        $this->fileProcessor = $fileProcessor;
        $this->extensionValidator = $extensionValidator;
        $this->protocolValidator = $protocolValidator;
        $this->storeManager = $storeManager;
        $this->configuration = $configuration;
        $this->mediaLibraryMapFactory = $mediaLibraryMapFactory;
        $this->assetRepository = $assetRepository;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        try {
            $localUniqFilePath = $this->remoteFileUrl = $this->getRequest()->getParam('remote_image');
            if (
                $this->configuration->isEnabledCachePlaceholder() &&
                strpos($this->getBaseTmpMediaPath(), '/category') === false
            ) {
                $customPlaceholder = $this->configuration->getCustomPlaceholderPath();
                if ($customPlaceholder && file_exists($customPlaceholder)) {
                    $image = file_get_contents($customPlaceholder);
                    $sourceFilePath = $customPlaceholder;
                } else {
                    $asset = $this->assetRepository->createAsset('Cloudinary_Cloudinary::images/cloudinary_placeholder.jpg');
                    $sourceFilePath = $asset->getSourceFile();
                    $image = file_get_contents($sourceFilePath);
                }
            } else {
                $image = file_get_contents($localUniqFilePath);
            }
            $this->validateRemoteFile($this->remoteFileUrl);
            $this->parsedRemoteFileUrl = $this->configuration->parseCloudinaryUrl($this->remoteFileUrl);
            $this->parsedRemoteFileUrl["transformations_string"] = $this->getRequest()->getParam('asset')["free_transformation"];
            $assetParsedRemoteFileUrl = $this->configuration->parseCloudinaryUrl($this->getRequest()->getParam('asset')["asset_url"]);
            $this->parsedRemoteFileUrl["type"] = $assetParsedRemoteFileUrl['type'];
            $this->parsedRemoteFileUrl["thumbnail_url"] = $assetParsedRemoteFileUrl['thumbnail_url'];
            $baseTmpMediaPath = $this->getBaseTmpMediaPath();

            if ($this->configuration->isEnabledLocalMapping()) {
                $this->cldUniqid = $this->configuration->generateCLDuniqid();
                $localUniqFilePath = $this->configuration->addUniquePrefixToBasename($localUniqFilePath, $this->cldUniqid);
            }

            $localUniqFilePath = $this->appendNewFileName($baseTmpMediaPath . $this->getLocalTmpFileName($localUniqFilePath));
            $this->validateFileExtensions($localUniqFilePath);

            // reads the image and save it locally
            if (!$this->configuration->isEnabledCachePlaceholder()) {
                // Only download the real Cloudinary image if not using placeholder
                $this->retrieveRemoteImage($this->remoteFileUrl, $localUniqFilePath);
            } else if ((strpos($this->getBaseTmpMediaPath(), '/category') !== false)) {
                    $this->retrieveRemoteImage($this->remoteFileUrl, $localUniqFilePath);
            } else {
                // Save the already-read placeholder image manually
                $this->fileUtility->saveFile($localUniqFilePath, $image);
                $this->usingPlaceholderFallback = true;
            }

            $localFileFullPath = $this->appendAbsoluteFileSystemPath($localUniqFilePath);
            $this->imageAdapter->validateUploadFile($localFileFullPath);
            $result = $this->appendResultSaveRemoteImage($localUniqFilePath, $baseTmpMediaPath);
            if ($this->configuration->isEnabledLocalMapping()) {
                $this->saveCloudinaryMapping();
            }
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
            $fileWriter = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA);
            if (isset($localFileFullPath) && $fileWriter->isExist($localFileFullPath)) {
                $fileWriter->delete($localFileFullPath);
            }
        }
        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }

    protected function getBaseTmpMediaPath()
    {
        $baseTmpMediaPath = false;
        switch ($this->getRequest()->getParam('type')) {
            case 'design_config_fileUploader':
                $baseTmpMediaPath = 'tmp/' . FileProcessor::FILE_DIR;
                break;
            case 'pagebuilder_contenttype':
                $baseTmpMediaPath = PageBuilderContentTypeUpload::UPLOAD_DIR;
                break;
            case 'category_image':
                $baseTmpMediaPath = 'catalog/tmp/category';
                break;
            default:
                $baseTmpMediaPath = $this->mediaConfig->getBaseTmpMediaPath();
                break;
        }
        if (!$baseTmpMediaPath) {
            throw new LocalizedException(__("Empty baseTmpMediaPath"));
        }
        return $baseTmpMediaPath;
    }

    protected function getLocalTmpFileName($remoteFileUrl)
    {
        $localFileName = Uploader::getCorrectFileName(basename($remoteFileUrl));
        switch ($this->getRequest()->getParam('type')) {
            case 'pagebuilder_contenttype':
            case 'design_config_fileUploader':
            case 'category_image':
                $localTmpFileName = DIRECTORY_SEPARATOR . $localFileName;
                break;
            default:
                $localTmpFileName = Uploader::getDispretionPath($localFileName) . DIRECTORY_SEPARATOR . $localFileName;
                break;
        }
        return $localTmpFileName;
    }

    /**
     * Validate remote file
     *
     * @throws LocalizedException
     *
     * @return $this
     */
    private function validateRemoteFile()
    {
        if (!$this->protocolValidator->isValid($this->remoteFileUrl)) {
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
    private function validateFileExtensions($filePath)
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
    protected function appendResultSaveRemoteImage($localUniqFilePath, $baseTmpMediaPath)
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
        $result['using_placeholder_fallback'] = (bool) $this->usingPlaceholderFallback;

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
    protected function retrieveRemoteImage($fileUrl, $localFilePath)
    {
        $this->curl->setConfig(['header' => false]);
        $this->curl->write('GET', $fileUrl);

        $image = $this->curl->read();

        if (empty($image) && $this->getRequest()->getParam('asset')["resource_type"] === 'video') {
            //Fallback for video thumbnail image, use placeholder or store logo
            $this->usingPlaceholderFallback = true;
            $this->curl->close();
            $this->curl->setConfig(['header' => false, 'verifypeer' => false, 'verifyhost' => 0]);
            $this->curl->write('GET', $this->getPlaceholderUrl());
            $image = $this->curl->read();
        }

        if (empty($image)) {
            $this->usingPlaceholderFallback = false;
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
    protected function appendNewFileName($localFilePath)
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
    protected function appendAbsoluteFileSystemPath($localTmpFile)
    {
        /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
        $mediaDirectory = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
        $pathToSave = $mediaDirectory->getAbsolutePath();
        return $pathToSave . $localTmpFile;
    }

    /**
     * @return string
     */
    private function saveCloudinaryMapping()
    {
        return $this->mediaLibraryMapFactory->create()
            ->setCldUniqid($this->cldUniqid)
            ->setCldPublicId(($this->parsedRemoteFileUrl["type"] === "video") ? $this->parsedRemoteFileUrl["thumbnail_url"] : $this->parsedRemoteFileUrl["publicId"] . '.' . $this->parsedRemoteFileUrl["extension"])
            ->setFreeTransformation($this->parsedRemoteFileUrl["transformations_string"])
            ->save();
    }

    /**
     * @return string
     */
    private function getPlaceholderUrl()
    {
        $configPaths = [
            'catalog/placeholder/image_placeholder',
            'catalog/placeholder/small_image_placeholder',
            'catalog/placeholder/thumbnail_placeholder',
        ];
        foreach ($configPaths as $configPath) {
            if (($path = $this->storeManager->getStore()->getConfig($configPath))) {
                return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/placeholder/' . $path;
                break;
            }
        }
        return $this->_view->getLayout()->createBlock("Magento\Theme\Block\Html\Header\Logo")->getViewFileUrl('Cloudinary_Cloudinary::images/cloudinary_cloud_glyph_blue.png');
    }
}
