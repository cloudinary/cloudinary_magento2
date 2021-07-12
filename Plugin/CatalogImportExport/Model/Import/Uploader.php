<?php

namespace Cloudinary\Cloudinary\Plugin\CatalogImportExport\Model\Import;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Model\MediaLibraryMapFactory;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Registry;

/**
 * Plugin for CatalogImportExport Uploader moduleList
 */
class Uploader
{
    /**
     * @var string|null
     */
    private $remoteFileUrl;

    /**
     * @var array
     */
    private $parsedRemoteFileUrl = [];

    /**
     * @var string|null
     */
    private $cldUniqid;

    /**
     * @var ProductMediaConfig
     */
    protected $mediaConfig;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var MediaLibraryMapFactory
     */
    private $mediaLibraryMapFactory;

    /**
     * @var TransformationFactory
     */
    private $transformationFactory;

    /**
     * Instance of filesystem directory write interface.
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_directory;

    /**
     * @method __construct
     * @param  ProductMediaConfig     $mediaConfig
     * @param  Filesystem             $fileSystem
     * @param  Registry               $coreRegistry
     * @param  ConfigurationInterface $configuration
     * @param  MediaLibraryMapFactory $mediaLibraryMapFactory
     * @param  TransformationFactory  $transformationFactory
     */
    public function __construct(
        ProductMediaConfig $mediaConfig,
        Filesystem $fileSystem,
        Registry $coreRegistry,
        ConfigurationInterface $configuration,
        MediaLibraryMapFactory $mediaLibraryMapFactory,
        TransformationFactory $transformationFactory
    ) {
        $this->mediaConfig = $mediaConfig;
        $this->fileSystem = $fileSystem;
        $this->coreRegistry = $coreRegistry;
        $this->configuration = $configuration;
        $this->mediaLibraryMapFactory = $mediaLibraryMapFactory;
        $this->transformationFactory = $transformationFactory;
        $this->_directory = $fileSystem->getDirectoryWrite(DirectoryList::ROOT);
    }

    /**
     * Prepare component configuration
     *
     * @param \Magento\CatalogImportExport\Model\Import\Uploader $uploaderModel
     * @param callable $proceed
     * @param string $fileName
     * @param bool $renameFileOff
     * @return array
     */
    public function aroundMove(\Magento\CatalogImportExport\Model\Import\Uploader $uploaderModel, callable $proceed, $fileName, $renameFileOff = false)
    {
        //= Before
        if ($this->configuration->isEnabled()) {
            $this->remoteFileUrl = $fileName;
            $this->parsedRemoteFileUrl = $this->configuration->parseCloudinaryUrl($this->remoteFileUrl);
            if ($this->parsedRemoteFileUrl["scheme"] && \strpos($this->parsedRemoteFileUrl["host"], "cloudinary.com") !== false) {
                $fileName = $this->parsedRemoteFileUrl['transformationless_url'];
                if ($this->parsedRemoteFileUrl['type'] === 'video') {
                    $fileName = $this->parsedRemoteFileUrl['thumbnail_url'];
                }
            } else {
                $this->parsedRemoteFileUrl["publicId"] = null;
            }
        }

        //===========================================//
        $result = $proceed($fileName, $renameFileOff);
        //===========================================//

        //= After
        if ($this->configuration->isEnabled() && $this->parsedRemoteFileUrl["publicId"]) {
            if ($this->configuration->isEnabledLocalMapping()) {
                $this->cldUniqid = $this->configuration->generateCLDuniqid();
                $catalogMediaPath = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . $this->mediaConfig->getBaseMediaPath();
                $result['name'] = $this->configuration->addUniquePrefixToBasename($result['name'], $this->cldUniqid);
                $_tmpName = $this->configuration->addUniquePrefixToBasename($result['tmp_name'], $this->cldUniqid);
                $_file = $this->configuration->addUniquePrefixToBasename($result['file'], $this->cldUniqid);
                $this->_directory->renameFile($result['tmp_name'], $_tmpName);
                $this->_directory->renameFile($catalogMediaPath . $result['file'], $catalogMediaPath . $_file);
                $result['tmp_name'] = $_tmpName;
                $result['file'] = $_file;

                $this->mediaLibraryMapFactory->create()
                    ->setCldUniqid($this->cldUniqid)
                    ->setCldPublicId(($this->parsedRemoteFileUrl["type"] === "video") ? $this->parsedRemoteFileUrl["thumbnail_url"] : $this->parsedRemoteFileUrl["publicId"] . '.' . $this->parsedRemoteFileUrl["extension"])
                    ->setFreeTransformation(\rawurldecode($this->parsedRemoteFileUrl["transformations_string"]))
                    ->save();
            }

            if ($this->parsedRemoteFileUrl["type"] === "image" && $this->parsedRemoteFileUrl['transformations_string']) {
                $this->transformationFactory->create()
                    ->setImageName($result['file'])
                    ->setFreeTransformation(\rawurldecode($this->parsedRemoteFileUrl["transformations_string"]))
                    ->save();
            }

            if ($this->parsedRemoteFileUrl['type'] === 'video') {
                $cloudinaryVideosImportMap = $this->coreRegistry->registry('cloudinary_videos_import_map') ?: [];
                $cloudinaryVideosImportMap["{$result['file']}"] = $this->parsedRemoteFileUrl["orig_url"];
                $this->coreRegistry->unregister('cloudinary_videos_import_map');
                $this->coreRegistry->register('cloudinary_videos_import_map', $cloudinaryVideosImportMap);
            }
        }

        return $result;
    }
}
