<?php

namespace Cloudinary\Cloudinary\Controller\Adminhtml\Cms\Wysiwyg\Images;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryResolver;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Registry;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Magento\MediaStorage\Model\ResourceModel\File\Storage\File;

/**
 * Upload image.
 */
class Upload extends \Magento\Cms\Controller\Adminhtml\Wysiwyg\Images\Upload
{

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var Config
     */
    protected $mediaConfig;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var AbstractAdapter
     */
    protected $imageAdapter;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var File
     */
    protected $fileUtility;

    /**
     * URI validator
     *
     * @var ValidatorInterface
     */
    private $protocolValidator;

    /**
     * @var NotProtectedExtension
     */
    private $extensionValidator;

    /**
     * @method __construct
     * @param  Context               $context
     * @param  Registry              $coreRegistry
     * @param  JsonFactory           $resultJsonFactory
     * @param  DirectoryResolver     $directoryResolver|null $directoryResolver
     * @param  DirectoryList         $directoryList
     * @param  Config                $mediaConfig
     * @param  Filesystem            $fileSystem
     * @param  AdapterFactory        $imageAdapterFactory
     * @param  Curl                  $curl
     * @param  File                  $fileUtility
     * @param  ValidatorInterface    $protocolValidator
     * @param  NotProtectedExtension $extensionValidator
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JsonFactory $resultJsonFactory,
        DirectoryResolver $directoryResolver = null,
        DirectoryList $directoryList,
        Config $mediaConfig,
        Filesystem $fileSystem,
        AdapterFactory $imageAdapterFactory,
        Curl $curl,
        File $fileUtility,
        ValidatorInterface $protocolValidator,
        NotProtectedExtension $extensionValidator
    ) {
        parent::__construct($context, $coreRegistry, $resultJsonFactory, $directoryResolver);
        $this->directoryList = $directoryList;
        $this->mediaConfig = $mediaConfig;
        $this->fileSystem = $fileSystem;
        $this->imageAdapter = $imageAdapterFactory->create();
        $this->curl = $curl;
        $this->fileUtility = $fileUtility;
        $this->extensionValidator = $extensionValidator;
        $this->protocolValidator = $protocolValidator;
    }

    /**
     * Files upload processing.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $this->_initAction();
            $path = $this->getStorage()->getSession()->getCurrentPath();
            if (!$this->validatePath($path, DirectoryList::MEDIA)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Directory %1 is not under storage root path.', $path)
                );
            }
            $remoteFileUrl = $this->getRequest()->getParam('remote_image');
            $this->validateRemoteFile($remoteFileUrl);
            $localFileName = Uploader::getCorrectFileName(basename($remoteFileUrl));
            $localFilePath = $path . DIRECTORY_SEPARATOR . $localFileName;
            $localFileFullPath = $this->appendNewFileName($localFilePath);
            $this->validateRemoteFileExtensions($localFileFullPath);
            $this->retrieveRemoteImage($remoteFileUrl, $localFileFullPath);
            $this->getStorage()->resizeFile($localFileFullPath, true);
            $this->imageAdapter->validateUploadFile($localFileFullPath);
            $result = $this->appendResultSaveRemoteImage($localFileFullPath);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($result);
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
     * Validate path.
     *
     * Gets real path for directory provided in parameters and compares it with specified root directory.
     * Will return TRUE if real path of provided value contains root directory path and FALSE if not.
     * Throws the \Magento\Framework\Exception\FileSystemException in case when directory path is absent
     * in Directories configuration.
     *
     * @param string $path
     * @param string $directoryConfig
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function validatePath($path, $directoryConfig = DirectoryList::MEDIA)
    {
        $directory = $this->filesystem->getDirectoryWrite($directoryConfig);
        $realPath = $directory->getDriver()->getRealPathSafety($path);
        $root = $this->directoryList->getPath($directoryConfig);

        return strpos($realPath, $root) === 0;
    }

    /**
     * Invalidates files that have script extensions.
     *
     * @param string $filePath
     * @throws \Magento\Framework\Exception\ValidatorException
     * @return void
     */
    private function validateRemoteFileExtensions($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $allowedExtensions = (array) $this->getStorage()->getAllowedExtensions($this->getRequest()->getParam('type'));
        if (!$this->extensionValidator->isValid($extension) || !in_array($extension, $allowedExtensions)) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Disallowed file type.'));
        }
    }

    /**
     * @param string $filePath
     * @return mixed
     */
    protected function appendResultSaveRemoteImage($filePath)
    {
        $fileInfo = pathinfo($filePath);
        $result['name'] = $fileInfo['basename'];
        $result['type'] = $this->imageAdapter->getMimeType();
        $result['error'] = 0;
        $result['size'] = filesize($filePath);
        $result['url'] = $this->getRequest()->getParam('remote_image');
        $result['file'] = $filePath;
        return $result;
    }

    /**
     * Trying to get remote image to save it locally
     *
     * @param string $fileUrl
     * @param string $localFilePath
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function retrieveRemoteImage($fileUrl, $localFilePath)
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
    protected function appendNewFileName($localFilePath)
    {
        $fileName = Uploader::getNewFileName($localFilePath);
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
}
