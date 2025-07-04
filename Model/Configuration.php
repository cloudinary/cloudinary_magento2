<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;
use Cloudinary\Cloudinary\Core\Cloud;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Credentials;
use Cloudinary\Cloudinary\Core\Exception\InvalidCredentials;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\DefaultImage;
use Cloudinary\Cloudinary\Core\Image\Transformation\Dpr;
use Cloudinary\Cloudinary\Core\Image\Transformation\FetchFormat;
use Cloudinary\Cloudinary\Core\Image\Transformation\Freeform;
use Cloudinary\Cloudinary\Core\Image\Transformation\Gravity;
use Cloudinary\Cloudinary\Core\Image\Transformation\Quality;
use Cloudinary\Cloudinary\Core\Security\CloudinaryEnvironmentVariable;
use Cloudinary\Cloudinary\Core\UploadConfig;
use Cloudinary\Cloudinary\Model\Logger as CloudinaryLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem;

class Configuration implements ConfigurationInterface
{
    const MODULE_NAME = 'Cloudinary_Cloudinary';

    //= Basics
    const CONFIG_PATH_ENABLED = 'cloudinary/cloud/cloudinary_enabled';
    const CONFIG_PATH_ENVIRONMENT_VARIABLE = 'cloudinary/setup/cloudinary_environment_variable';
    const CONFIG_PATH_CDN_SUBDOMAIN = 'cloudinary/configuration/cloudinary_cdn_subdomain';

    //= Transformations
    const CONFIG_PATH_DEFAULT_GRAVITY = 'cloudinary/transformations/cloudinary_gravity';
    const CONFIG_PATH_DEFAULT_QUALITY = 'cloudinary/transformations/cloudinary_image_quality';
    const CONFIG_PATH_DEFAULT_DPR = 'cloudinary/transformations/cloudinary_image_dpr';
    const CONFIG_PATH_DEFAULT_FETCH_FORMAT = 'cloudinary/transformations/cloudinary_fetch_format';
    const CONFIG_PATH_DEFAULT_IMAGE = 'cloudinary/transformations/cloudinary_default_image';
    const CONFIG_PATH_GLOBAL_FREEFORM = 'cloudinary/transformations/cloudinary_free_transform_global';
    const CONFIG_PATH_GLOBAL_FREEFORM_PRODUCTS = 'cloudinary/transformations/cloudinary_free_transform_global_products';
    const CONFIG_PATH_GLOBAL_FREEFORM_PRODUCTS_BEHAVIOR = 'cloudinary/transformations/cloudinary_free_transform_global_products_behavior';

    //= Lazyload
    const XML_PATH_LAZYLOAD_ENABLED = 'cloudinary/lazyload/enabled';
    const XML_PATH_LAZYLOAD_AUTO_REPLACE_CMS_BLOCKS = 'cloudinary/lazyload/auto_replace_cms_blocks';
    const XML_PATH_LAZYLOAD_IGNORED_CMS_BLOCKS = 'cloudinary/lazyload/ignored_cms_blocks';
    const XML_PATH_LAZYLOAD_THRESHOLD = 'cloudinary/lazyload/threshold';
    const XML_PATH_LAZYLOAD_EFFECT = 'cloudinary/lazyload/effect';
    const XML_PATH_LAZYLOAD_PLACEHOLDER = 'cloudinary/lazyload/placeholder';

    //= Advanced
    const CONFIG_PATH_REMOVE_VERSION_NUMBER = 'cloudinary/advanced/remove_version_number';
    const CONFIG_PATH_USE_ROOT_PATH = 'cloudinary/advanced/use_root_path';
    const CONFIG_PATH_USE_SIGNED_URLS = 'cloudinary/advanced/use_signed_urls';
    const CONFIG_PATH_ENABLE_LOCAL_MAPPING = 'cloudinary/advanced/enable_local_mapping';
    const CONFIG_PATH_SCHEDULED_VIDEO_DATA_IMPORT_LIMIT = 'cloudinary/advanced/cloudinary_scheduled_video_data_import_limit';
    const CONFIG_PATH_PG_API_QUEUE_ENABLED = 'cloudinary/advanced/product_gallery_api_queue_enabled';
    const CONFIG_PATH_PG_API_QUEUE_LIMIT = 'cloudinary/advanced/product_gallery_api_queue_limit';
    const CONFIG_PATH_PG_API_QUEUE_MAX_TRYOUTS = 'cloudinary/advanced/product_gallery_api_queue_max_tryouts';
    const CONFIG_PATH_ENABLE_PRODUCT_FREE_TRANSFORMATIONS = 'cloudinary/advanced/enable_product_free_transformations';
    const CONFIG_PATH_LOAD_SWATCHES_FROM_CLOUDINARY = 'cloudinary/advanced/load_cloudinary_swatches';

    const CONFIG_PATH_ENABLE_CACHE_PLACEHOLDER = 'cloudinary/advanced/cache_placeholder_enable';

    const CONFIG_PATH_CUSTOM_PLACEHOLDER_IMAGE = 'cloudinary/advanced/custom_placeholder_image';


    //= Product Gallery
    const CONFIG_PATH_PG_ALL = 'cloudinary/product_gallery';
    const CONFIG_PATH_PG_ENABLED = 'cloudinary/product_gallery/enabled';
    const CONFIG_PATH_PG_THEMEPROPS_PRIMARY = 'cloudinary/product_gallery/themeProps_primary';
    const CONFIG_PATH_PG_THEMEPROPS_ONPRIMARY = 'cloudinary/product_gallery/themeProps_onPrimary';
    const CONFIG_PATH_PG_THEMEPROPS_ACTIVE = 'cloudinary/product_gallery/themeProps_active';
    const CONFIG_PATH_PG_THEMEPROPS_ONACTIVE = 'cloudinary/product_gallery/themeProps_onActive';
    const CONFIG_PATH_PG_TRANSITION = 'cloudinary/product_gallery/transition';
    const CONFIG_PATH_PG_ASPECT_RATIO = 'cloudinary/product_gallery/aspectRatio';
    const CONFIG_PATH_PG_ZOOMPROPS_NAVIGATION = 'cloudinary/product_gallery/navigation';
    const CONFIG_PATH_PG_ZOOM = 'cloudinary/product_gallery/zoom';
    const CONFIG_PATH_PG_ZOOMPROPS_TYPE = 'cloudinary/product_gallery/zoomProps_type';
    const CONFIG_PATH_PG_ZOOMPROPS_POSITION = 'cloudinary/product_gallery/zoomPropsViewerPosition';
    const CONFIG_PATH_PG_ZOOMPROPS_TRIGGER = 'cloudinary/product_gallery/zoomProps_trigger';
    const CONFIG_PATH_PG_CAROUSEL_LOCATION = 'cloudinary/product_gallery/carouselLocation';
    const CONFIG_PATH_PG_CAROUSEL_OFFSET = 'cloudinary/product_gallery/carouselOffset';
    const CONFIG_PATH_PG_CAROUSEL_STYLE = 'cloudinary/product_gallery/carouselStyle';
    const CONFIG_PATH_PG_THUMBNAILPROPS_WIDTH = 'cloudinary/product_gallery/thumbnailProps_width';
    const CONFIG_PATH_PG_THUMBNAILPROPS_HEIGHT = 'cloudinary/product_gallery/thumbnailProps_height';
    const CONFIG_PATH_PG_THUMBNAILPROPS_NAVIGATION_SHAPE = 'cloudinary/product_gallery/thumbnailProps_navigationShape';
    const CONFIG_PATH_PG_THUMBNAILPROPS_SELECTED_STYLE = 'cloudinary/product_gallery/thumbnailProps_selectedStyle';
    const CONFIG_PATH_PG_THUMBNAILPROPS_SELECTED_BORDER_POSITION = 'cloudinary/product_gallery/thumbnailProps_selectedBorderPosition';
    const CONFIG_PATH_PG_THUMBNAILPROPS_SELECTED_BORDER_WIDTH = 'cloudinary/product_gallery/thumbnailProps_selectedBorderWidth';
    const CONFIG_PATH_PG_THUMBNAILPROPS_MEDIA_ICON_SHAPE = 'cloudinary/product_gallery/thumbnailProps_mediaSymbolShape';
    const CONFIG_PATH_PG_INDICATORPROPS_SHAPE = 'cloudinary/product_gallery/indicatorProps_shape';
    const CONFIG_PATH_PG_CUSTOM_FREE_PARAMS = 'cloudinary/product_gallery/custom_free_params';

    // Video Settings

    const CONFIG_PATH_CLD_VIDEO_SETTINGS_ALL = 'cloudinary/cld_video';
    const CONFIG_PATH_CLD_VIDEO_ENABLED = 'cloudinary/cld_video/enabled';
    const CONFIG_PATH_CLD_VIDEO_PLAYER_USE_ABR = 'cloudinary/cld_video/use_abr';
    const CONFIG_PATH_CLD_VIDEO_PLAYER_SHOW_CONTROLS = 'cloudinary/cld_video/controls';
    const CONFIG_PATH_CLD_VIDEO_PLAYER_LOOP = 'cloudinary/cld_video/loop';
    const CONFIG_PATH_CLD_VIDEO_PLAYER_AUTOPLAY = 'cloudinary/cld_video/autoplay';


    //= Others
    const CONFIG_PATH_SECURE_BASE_URL = "web/secure/base_url";
    const CONFIG_PATH_UNSECURE_BASE_URL = "web/unsecure/base_url";
    const CONFIG_PATH_USE_SECURE_IN_FRONTEND = "web/secure/use_in_frontend";

    const USER_PLATFORM_TEMPLATE = 'CloudinaryMagento/%s (Magento %s)';
    const USE_FILENAME = true;
    const UNIQUE_FILENAME = false;
    const OVERWRITE = false;
    const SCOPE_ID_ONE = 1;
    const SCOPE_ID_ZERO = 0;
    const CLD_UNIQID_PREFIX = 'cld_';

    const LAZYLOAD_DATA_PLACEHOLDER = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAANSURBVBhXYzh8+PB/AAffA0nNPuCLAAAAAElFTkSuQmCC';

    /**
     * @var ScopeConfigInterface
     */
    private $configReader;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var EncryptorInterface
     */
    private $decryptor;

    /**
     * @var
     */
    private $environmentVariable;

    /**
     * @var AutoUploadConfigurationInterface
     */
    private $autoUploadConfiguration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var CloudinaryLogger
     */
    private $cloudinaryLogger;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    protected $directoryList;

    private $filesystem;

    /**
     * @param ScopeConfigInterface $configReader
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $decryptor
     * @param AutoUploadConfigurationInterface $autoUploadConfiguration
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     * @param ProductMetadataInterface $productMetadata
     * @param Logger $cloudinaryLogger
     * @param Registry $coreRegistry
     * @param ManagerInterface $messageManager
     * @param Filesystem $filesystem
     */
    public function __construct(
        ScopeConfigInterface $configReader,
        WriterInterface $configWriter,
        EncryptorInterface $decryptor,
        AutoUploadConfigurationInterface $autoUploadConfiguration,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        CloudinaryLogger $cloudinaryLogger,
        Registry $coreRegistry,
        ManagerInterface $messageManager,
        Filesystem $filesystem,
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->decryptor = $decryptor;
        $this->autoUploadConfiguration = $autoUploadConfiguration;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->cloudinaryLogger = $cloudinaryLogger;
        $this->coreRegistry = $coreRegistry;
        $this->messageManager = $messageManager;
        $this->filesystem = $filesystem;
    }

    /**
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * @return Registry
     */
    public function getCoreRegistry()
    {
        return $this->coreRegistry;
    }

    /**
     * @return Cloud
     */
    public function getCloud()
    {
        return $this->getEnvironmentVariable()->getCloud();
    }

    /**
     * @return (array) Credentials
     */
    public function getCredentials()
    {

            $rawValue =  $this->configReader->getValue(self::CONFIG_PATH_ENVIRONMENT_VARIABLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $value = $this->decryptor->decrypt($rawValue);
            $environmentVariable = str_replace('CLOUDINARY_URL=', '', $value);
            $uri = parse_url($environmentVariable);
            if (!isset($uri["scheme"]) || strtolower($uri["scheme"]) !== "cloudinary") {
                throw new \InvalidArgumentException("Invalid CLOUDINARY_URL scheme. Expecting to start with 'cloudinary://'");
            }
            $q_params = [];
            if (isset($uri["query"])) {
                parse_str($uri["query"], $q_params);
            }
            $private_cdn = isset($uri["path"]) && $uri["path"] != "/";

            $credentials = array_merge(
                $q_params,
                [
                    "cloud_name" => $uri["host"],
                    "api_key" => $uri["user"],
                    "api_secret" => $uri["pass"],
                    "private_cdn" => $private_cdn,
                ]
            );

            if (isset($credentials['cname'])) {
                $credentials['secure'] = true;
                $credentials['secure_distribution'] = $credentials['cname'];
            }

            return $credentials;


    }

    /**
     * @param  bool $isProduct
     * @return Transformation
     */
    public function getDefaultTransformation($isProduct = false)
    {
        if ($isProduct && ($globalFreeform = $this->getDefaultGlobalFreeformProducts())) {
            if ($this->getDefaultGlobalFreeformProductsBehavior() === 'add') {
                $globalFreeform = $this->getDefaultGlobalFreeform() . ',' . $globalFreeform;
            }
        } else {
            $globalFreeform = $this->getDefaultGlobalFreeform();
        }

        return Transformation::builder()
            ->withGravity(Gravity::fromString($this->getDefaultGravity()))
            ->withQuality(Quality::fromString($this->getImageQuality()))
            ->withFetchFormat(FetchFormat::fromString($this->getFetchFormat()))
            ->withFreeform(Freeform::fromString($globalFreeform))
            ->withDpr(Dpr::fromString($this->getImageDpr()))
            ->withDefaultImage(DefaultImage::fromString($this->getCloudinaryDefaultImage()));
    }

    /**
     * @return string
     */
    private function getDefaultGlobalFreeform()
    {
        return (string) $this->configReader->getValue(self::CONFIG_PATH_GLOBAL_FREEFORM);
    }

    /**
     * @return string
     */
    private function getDefaultGlobalFreeformProducts()
    {
        return (string) $this->configReader->getValue(self::CONFIG_PATH_GLOBAL_FREEFORM_PRODUCTS);
    }

    /**
     * @return string
     */
    private function getDefaultGlobalFreeformProductsBehavior()
    {
        return (string) $this->configReader->getValue(self::CONFIG_PATH_GLOBAL_FREEFORM_PRODUCTS_BEHAVIOR);
    }

    /**
     * @return boolean
     */
    public function getCdnSubdomainStatus()
    {
        return $this->configReader->isSetFlag(self::CONFIG_PATH_CDN_SUBDOMAIN);
    }

    /**
     * @return string
     */
    public function getUserPlatform()
    {
        return sprintf(self::USER_PLATFORM_TEMPLATE, $this->getModuleVersion(), $this->getMagentoPlatformVersion());
    }

    /**
     * @return UploadConfig
     */
    public function getUploadConfig()
    {
        return UploadConfig::fromBooleanValues(self::USE_FILENAME, self::UNIQUE_FILENAME, self::OVERWRITE);
    }

    /**
     * @return bool
     */
    public function isModuleEnabled()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_ENABLED);
    }
    /**
     * @return boolean
     */
    public function isEnabled($checkEnvVar = true)
    {
        return ($this->hasEnvironmentVariable() || !$checkEnvVar) && ($this->coreRegistry->registry(self::CONFIG_PATH_ENABLED) || $this->configReader->isSetFlag(self::CONFIG_PATH_ENABLED));
    }

    public function enable()
    {
        $this->configWriter->save(self::CONFIG_PATH_ENABLED, self::SCOPE_ID_ONE);
    }

    public function disable()
    {
        $this->configWriter->save(self::CONFIG_PATH_ENABLED, self::SCOPE_ID_ZERO);
    }

    /**
     * @return array
     */
    public function getFormatsToPreserve()
    {
        return ['png', 'webp', 'gif', 'svg'];
    }

    /**
     * @return array
     */
    public function getSupportedVideoFormats()
    {
        return ['mp4', 'webm', 'ogv', 'mov', 'wmv'];
    }

    /**
     * @param  string $file
     * @return string
     */
    public function getMigratedPath($file)
    {
        return preg_match("#^" . preg_quote(DirectoryList::MEDIA . DIRECTORY_SEPARATOR, '/') . "#i", $file) ? $file : sprintf('%s/%s', DirectoryList::MEDIA, $file);
    }

    /**
     * @return string
     */
    public function getDefaultGravity()
    {
        return (string) $this->configReader->getValue(self::CONFIG_PATH_DEFAULT_GRAVITY);
    }

    /**
     * @return string
     */
    public function getFetchFormat()
    {
        return $this->configReader->isSetFlag(self::CONFIG_PATH_DEFAULT_FETCH_FORMAT) ? FetchFormat::FETCH_FORMAT_AUTO : '';
    }

    /**
     * @return string
     */
    public function getCloudinaryDefaultImage()
    {
        return (string) $this->configReader->getValue(self::CONFIG_PATH_DEFAULT_IMAGE);
    }

    /**
     * @return string
     */
    public function getImageQuality()
    {
        return (string) $this->configReader->getValue(self::CONFIG_PATH_DEFAULT_QUALITY);
    }

    /**
     * @return string
     */
    public function getImageDpr()
    {
        return $this->configReader->getValue(self::CONFIG_PATH_DEFAULT_DPR);
    }

    /**
     * @return bool
     */
    public function hasEnvironmentVariable()
    {
        return $this->coreRegistry->registry(self::CONFIG_PATH_ENVIRONMENT_VARIABLE) ?: (bool)$this->configReader->getValue(self::CONFIG_PATH_ENVIRONMENT_VARIABLE);
    }

    /**
     * @return ManagerInterface
     */
    public function getEnvironmentVariable()
    {
        if (is_null($this->environmentVariable)) {
            try {
                $field = $this->coreRegistry->registry(self::CONFIG_PATH_ENVIRONMENT_VARIABLE);
                $value =   $field ?: $this->decryptor->decrypt($this->configReader->getValue(self::CONFIG_PATH_ENVIRONMENT_VARIABLE));
                if (!$value) {
                    $this->messageManager->addError('Invalid Cloudinary credentails. please check your credentials and try again.');
                }
                $this->environmentVariable = CloudinaryEnvironmentVariable::fromString($value);
            } catch (InvalidCredentials $invalidConfigException) {
                $this->logger->critical($invalidConfigException);
            }
        }

        return $this->environmentVariable;
    }

    /**
     * @return bool
     */
    public function isEnabledProductGallery()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_PG_ENABLED);
    }

    /**
     * @return array
     */
    public function getProductGalleryAll()
    {
        return (array) $this->configReader->getValue(self::CONFIG_PATH_PG_ALL);
    }


    public function getAllVideoSettings()
    {
        return (array) $this->configReader->getValue(self::CONFIG_PATH_CLD_VIDEO_SETTINGS_ALL);
    }

    public function isLoadSwatchesFromCloudinary()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_LOAD_SWATCHES_FROM_CLOUDINARY);
    }

    public function isEnabledCachePlaceholder()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_ENABLE_CACHE_PLACEHOLDER);
    }

    public function getCustomPlaceholderPath(): ?string
    {
        $fileName = $this->configReader->getValue(self::CONFIG_PATH_CUSTOM_PLACEHOLDER_IMAGE);

        if (!$fileName) {
            return null;
        }

        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $filePath = 'cloudinary_placeholder/' . ltrim($fileName, '/');

        if ($mediaDirectory->isExist($filePath)) {
            return $mediaDirectory->getAbsolutePath($filePath);
        }

        return null;
    }

    public function isEnabledLazyload()
    {
        return (bool) $this->configReader->getValue(self::XML_PATH_LAZYLOAD_ENABLED);
    }

    public function isLazyloadAutoReplaceCmsBlocks()
    {
        return (bool) $this->configReader->getValue(self::XML_PATH_LAZYLOAD_AUTO_REPLACE_CMS_BLOCKS);
    }

    /**
     * @return array
     */
    public function getLazyloadIgnoredCmsBlocksArray()
    {
        $value = ($this->configReader->getValue(self::XML_PATH_LAZYLOAD_IGNORED_CMS_BLOCKS))
            ? (array) explode(',', $this->configReader->getValue(self::XML_PATH_LAZYLOAD_IGNORED_CMS_BLOCKS))
            : [];

        return $value;
    }

    public function getLazyloadThreshold()
    {
        return (int) $this->configReader->getValue(self::XML_PATH_LAZYLOAD_THRESHOLD);
    }

    public function getLazyloadEffect()
    {
        return (string) $this->configReader->getValue(self::XML_PATH_LAZYLOAD_EFFECT);
    }

    public function getLazyloadPlaceholder()
    {
        return (string) $this->configReader->getValue(self::XML_PATH_LAZYLOAD_PLACEHOLDER);
    }

    /**
     * @return Freeform
     */
    public function getLazyloadPlaceholderFreeform($placeholderType = null)
    {
        $placeholderType = $placeholderType ?: $this->getLazyloadPlaceholder();
        switch ($placeholderType) {
            case 'pixelate':
                $freeTransform = 'q_1,e_pixelate';
                break;

            case 'predominant-color':
                $freeTransform = '$currWidth_w,$currHeight_h/w_iw_div_2,ar_1,c_pad,b_auto/c_crop,w_10,h_10,g_north_east/w_$currWidth,h_$currHeight,c_fill/q_1';
                break;

            case 'vectorize':
                $freeTransform = 'q_1,e_vectorize:3:0.1';
                break;

            case 'blur':
            default:
                $freeTransform = 'q_1,e_blur:2000';
                break;
        }
        return Freeform::fromString($freeTransform);
        //return Transformation::builder()->withFreeform(Freeform::fromString($freeTransform));
    }

    /**
     * @return bool
     */
    public function getRemoveVersionNumber()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_REMOVE_VERSION_NUMBER);
    }

    /**
     * @return bool
     */
    public function getUseRootPath()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_USE_ROOT_PATH);
    }

    /**
     * @return bool
     */
    public function getUseSignedUrls()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_USE_SIGNED_URLS);
    }

    /**
     * @return bool
     */
    public function isEnabledLocalMapping()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_ENABLE_LOCAL_MAPPING);
    }

    /**
     * @return bool
     */
    public function isEnabledProductFreeTransformations()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_ENABLE_PRODUCT_FREE_TRANSFORMATIONS);
    }

    /**
     * @return bool
     */
    public function getScheduledVideoDataImportLimit()
    {
        return (int) $this->configReader->getValue(self::CONFIG_PATH_SCHEDULED_VIDEO_DATA_IMPORT_LIMIT);
    }

    /**
     * @return bool
     */
    public function isEnabledProductgalleryApiQueue()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_PG_API_QUEUE_ENABLED);
    }

    /**
     * @return bool
     */
    public function getProductgalleryApiQueueLimit()
    {
        $return = (int) $this->configReader->getValue(self::CONFIG_PATH_PG_API_QUEUE_LIMIT);
        if ($return < 0) {
            return 0;
        }
        return $return;
    }

    /**
     * @return bool
     */
    public function getProductgalleryApiQueueMaxTryouts()
    {
        $return = (int) $this->configReader->getValue(self::CONFIG_PATH_PG_API_QUEUE_MAX_TRYOUTS);
        if ($return > 20) {
            return 20;
        }
        if ($return < 1) {
            return 5;
        }
        return $return;
    }

    /**
     * @method getMediaBaseUrl
     * @return string
     */
    public function getMediaBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getMagentoPlatformName()
    {
        return $this->productMetadata->getName();
    }

    public function getMagentoPlatformEdition()
    {
        return $this->productMetadata->getEdition();
    }

    public function getMagentoPlatformVersion()
    {
        return $this->productMetadata->getVersion();
    }

    public function isEnabledCldVideo()
    {
        return  $this->configReader->getValue(self::CONFIG_PATH_CLD_VIDEO_ENABLED);
    }

    public function getAbrMode()
    {
        return  $this->configReader->getValue(self::CONFIG_PATH_CLD_VIDEO_PLAYER_USE_ABR);
    }

    public function isCldVideoLoop()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_CLD_VIDEO_PLAYER_LOOP);
    }

    public function showCldVideoControls()
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_CLD_VIDEO_PLAYER_SHOW_CONTROLS);
    }

    public function getCldVideoAutoplayMode()
    {
        return  $this->configReader->getValue(self::CONFIG_PATH_CLD_VIDEO_PLAYER_AUTOPLAY);
    }

    public function mediaRelativePath($filepath)
    {
        $pubPath = DirectoryList::getPath(DirectoryList::PUB) . DIRECTORY_SEPARATOR;
        return (strpos($filepath, $pubPath) === 0) ? str_replace($pubPath, '', $filepath) : $filepath;
    }

    /**
     * Parse Cloudinary URL
     * @method parseCloudinaryUrl
     * @param  string             $url
     * @param  string|null        $publicId
     * @return array
     */
    public function parseCloudinaryUrl($url, $publicId = null)
    {
        $parsedUrlParts = $this->mbParseUrl($url);
        $url = preg_replace('/\?.*/', '', $url);

        $parsed = [
            "orig_url" => $url,
            "scheme" => isset($parsedUrlParts["scheme"]) ? $parsedUrlParts["scheme"] : null,
            "host" => isset($parsedUrlParts["host"]) ? $parsedUrlParts["host"] : null,
            "path" => isset($parsedUrlParts["path"]) ? $parsedUrlParts["path"] : null,
            "query" => isset($parsedUrlParts["query"]) ? $parsedUrlParts["query"] : null,
            "extension" => \pathinfo($url, PATHINFO_EXTENSION),
            "type" => null,
            "cloudName" => null,
            "version" => null,
            "publicId" => ltrim((string) $publicId, '/') ?: null,
            "transformations_string" => null,
            "transformations" => [],
            "transformationless_url" => $url,
            "versionless_url" => $url,
            "versionless_transformationless_url" => $url,
            "thumbnail_url" => null,
        ];

        $_url = ltrim($parsed["path"], '/');
        $_url = preg_replace('/\.[^.]+$/', '', $_url);

        preg_match('/\/v[0-9]{1,10}\//', $_url, $version);
        if ($version && isset($version[0])) {
            $parsed["version"] = trim($version[0], '/');
        }

        if (!$parsed["publicId"] && $parsed["version"]) {
            $parsed["publicId"] = preg_replace('/.+\/v[0-9]{1,10}\//', '', $_url);
        }

        $_url = preg_replace('/(\/|\/v[0-9]{1,10}\/)' . \preg_quote((string) $parsed["publicId"], '/') . '$/', '', $_url);
        $_url = explode('/', $_url);

        $slug = \array_shift($_url);
        if (\in_array($slug, ["image","video"])) {
            $parsed["type"] = $slug;
        } else {
            $parsed["cloudName"] = $slug;
        }

        $slug = \array_shift($_url);
        $parsed["type"] = ($parsed["cloudName"] && $slug  === "video") ? "video" : "image";

        if (isset($parsed['extension'])) {
            $parsed['type'] = (in_array($parsed['extension'], $this->getSupportedVideoFormats())) ? 'video' : 'image';
        }

        $slug = \array_shift($_url);
        $parsed["transformations_string"] = ($slug === 'upload' ? '' : $slug) . implode('/', $_url);

        if ($parsed["transformations_string"]) {
            $parsed["transformations"] = explode(',', \str_replace('/', ',', $parsed["transformations_string"]));
            $parsed["transformationless_url"] = preg_replace('/\/' . \preg_quote($parsed["transformations_string"], '/') . '\//', '/', $url, 1);
        }

        $parsed["versionless_url"] = preg_replace('/\/v[0-9]{1,10}\//', '/', $url, 1);
        $parsed["versionless_transformationless_url"] = preg_replace('/\/v[0-9]{1,10}\//', '/', $parsed["transformationless_url"], 1);

        if ($parsed["type"] === "video") {
            $parsed["thumbnail_url"] = preg_replace('/\.[^.]+$/', '', $url);
            $parsed["thumbnail_url"] = preg_replace('/\/v[0-9]{1,10}\//', '/', $parsed["thumbnail_url"]);
            $parsed["thumbnail_url"] = preg_replace('/\/(' . \preg_quote((string) $parsed["publicId"], '/') . ')$/', '/so_auto/$1.jpg', $parsed["thumbnail_url"]);
        }

        return $parsed;
    }

    /**
     * UTF-8 aware parse_url() replacement.
     *
     * @return array
     */
    public function mbParseUrl($url, $component = -1)
    {
        $enc_url = preg_replace_callback(
            '%[^:/@?&=#]+%usD',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $url
        );
        $parts = parse_url($enc_url, $component);
        if ($parts === false) {
            throw new \InvalidArgumentException('Malformed URL: ' . $url);
        }
        if (is_array($parts)) {
            foreach ($parts as $name => $value) {
                $parts[$name] = rawurldecode($value);
            }
        } else {
            $parts = rawurldecode($parts);
        }
        return $parts;
    }

    public function generateCLDuniqid()
    {
        return strtolower(uniqid(self::CLD_UNIQID_PREFIX)) . '_';
    }

    public function addUniquePrefixToBasename($filename, $uniqid = null)
    {
        $uniqid = $uniqid ? $uniqid : $this->generateCLDuniqid();
        return dirname($filename) . '/' . $uniqid . basename($filename);
    }

    /**
     * Log to var/log/cloudinary_cloudinary.log
     * @method log
     * @param  mixed  $message
     * @param  array  $data
     * @return $this
     */
    public function log($message, $data = [], $prefix = '[Cloudinary Log] ')
    {
        $this->cloudinaryLogger->info($prefix . json_encode($message), $data);
        return $this;
    }

    /**
     * @method setRegistryEnabled
     * @param  string|null            $val
     */
    public function setRegistryEnabled($val)
    {
        $this->coreRegistry->register(self::CONFIG_PATH_ENABLED, $val);
        return $this;
    }

    /**
     * @method setRegistryEnvVar
     * @param  bool            $val
     */
    public function setRegistryEnvVar($val)
    {
        $this->coreRegistry->register(self::CONFIG_PATH_ENVIRONMENT_VARIABLE, ($val ? true : false));
        return $this;
    }
}
