<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;
use Cloudinary\Cloudinary\Core\Cloud;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Credentials;
use Cloudinary\Cloudinary\Core\Exception\InvalidCredentials;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\Dpr;
use Cloudinary\Cloudinary\Core\Image\Transformation\FetchFormat;
use Cloudinary\Cloudinary\Core\Image\Transformation\Freeform;
use Cloudinary\Cloudinary\Core\Image\Transformation\Gravity;
use Cloudinary\Cloudinary\Core\Image\Transformation\Quality;
use Cloudinary\Cloudinary\Core\Security\CloudinaryEnvironmentVariable;
use Cloudinary\Cloudinary\Core\UploadConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Configuration implements ConfigurationInterface
{
    const CONFIG_PATH_ENABLED = 'cloudinary/cloud/cloudinary_enabled';
    const USER_PLATFORM_TEMPLATE = 'CloudinaryMagento/%s (Magento %s)';
    const CONFIG_PATH_ENVIRONMENT_VARIABLE = 'cloudinary/setup/cloudinary_environment_variable';
    const CONFIG_CDN_SUBDOMAIN = 'cloudinary/configuration/cloudinary_cdn_subdomain';
    //= Transformations
    const CONFIG_DEFAULT_GRAVITY = 'cloudinary/transformations/cloudinary_gravity';
    const CONFIG_DEFAULT_QUALITY = 'cloudinary/transformations/cloudinary_image_quality';
    const CONFIG_DEFAULT_DPR = 'cloudinary/transformations/cloudinary_image_dpr';
    const CONFIG_DEFAULT_FETCH_FORMAT = 'cloudinary/transformations/cloudinary_fetch_format';
    const CONFIG_GLOBAL_FREEFORM = 'cloudinary/transformations/cloudinary_free_transform_global';
    //= Advanced
    const CONFIG_PATH_REMOVE_VERSION_NUMBER = 'cloudinary/advanced/remove_version_number';
    const CONFIG_PATH_USE_ROOT_PATH = 'cloudinary/advanced/use_root_path';
    //= Others
    const CONFIG_PATH_SECURE_BASE_URL = "web/secure/base_url";
    const CONFIG_PATH_UNSECURE_BASE_URL = "web/unsecure/base_url";
    const CONFIG_PATH_USE_SECURE_IN_FRONTEND = "web/secure/use_in_frontend";

    const USE_FILENAME = true;
    const UNIQUE_FILENAME = false;
    const OVERWRITE = false;
    const SCOPE_ID_ONE = 1;
    const SCOPE_ID_ZERO = 0;

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
     * @var CloudinaryEnvironmentVariable
     */
    private $environmentVariable;

    /**
     * @var AutoUploadConfigurationInterface
     */
    private $autoUploadConfiguration;

    /**
     * @param ScopeConfigInterface $configReader
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $decryptor
     * @param AutoUploadConfigurationInterface $autoUploadConfiguration
     */
    public function __construct(
        ScopeConfigInterface $configReader,
        WriterInterface $configWriter,
        EncryptorInterface $decryptor,
        AutoUploadConfigurationInterface $autoUploadConfiguration,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->decryptor = $decryptor;
        $this->autoUploadConfiguration = $autoUploadConfiguration;
        $this->logger = $logger;
    }

    /**
     * @return Cloud
     */
    public function getCloud($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->getEnvironmentVariable($scope, $scopeId)->getCloud();
    }

    /**
     * @return Credentials
     */
    public function getCredentials($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->getEnvironmentVariable($scope, $scopeId)->getCredentials();
    }

    /**
     * @return Transformation
     */
    public function getDefaultTransformation($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return Transformation::builder()
            ->withGravity(Gravity::fromString($this->getDefaultGravity($scope, $scopeId)))
            ->withQuality(Quality::fromString($this->getImageQuality($scope, $scopeId)))
            ->withFetchFormat(FetchFormat::fromString($this->getFetchFormat($scope, $scopeId)))
            ->withFreeform(Freeform::fromString($this->getDefaultGlobalFreeform($scope, $scopeId)))
            ->withDpr(Dpr::fromString($this->getImageDpr($scope, $scopeId)));
    }

    /**
     * @return string
     */
    private function getDefaultGlobalFreeform($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return (string) $this->configReader->getValue(self::CONFIG_GLOBAL_FREEFORM, $scope, $scopeId);
    }

    /**
     * @return boolean
     */
    public function getCdnSubdomainStatus($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->configReader->isSetFlag(self::CONFIG_CDN_SUBDOMAIN, $scope, $scopeId);
    }

    /**
     * @return string
     */
    public function getUserPlatform()
    {
        return sprintf(self::USER_PLATFORM_TEMPLATE, '1.6.2', '2.0.0');
    }

    /**
     * @return UploadConfig
     */
    public function getUploadConfig()
    {
        return UploadConfig::fromBooleanValues(self::USE_FILENAME, self::UNIQUE_FILENAME, self::OVERWRITE);
    }

    /**
     * @return boolean
     */
    public function isEnabled($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->hasEnvironmentVariable() && $this->configReader->isSetFlag(self::CONFIG_PATH_ENABLED, $scope, $scopeId);
    }

    public function enable($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        $this->configWriter->save(self::CONFIG_PATH_ENABLED, self::SCOPE_ID_ONE, $scope, $scopeId);
    }

    public function disable($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        $this->configWriter->save(self::CONFIG_PATH_ENABLED, self::SCOPE_ID_ZERO, $scope, $scopeId);
    }

    /**
     * @return array
     */
    public function getFormatsToPreserve()
    {
        return ['png', 'webp', 'gif', 'svg'];
    }

    /**
     * @param string $file
     * @return string
     */
    public function getMigratedPath($file)
    {
        return $this->autoUploadConfiguration->isActive() ? sprintf('%s/%s', DirectoryList::MEDIA, $file) : $file;
    }

    /**
     * @return string
     */
    public function getDefaultGravity($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return (string) $this->configReader->getValue(self::CONFIG_DEFAULT_GRAVITY, $scope, $scopeId);
    }

    /**
     * @return string
     */
    public function getFetchFormat($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->configReader->isSetFlag(self::CONFIG_DEFAULT_FETCH_FORMAT, $scope, $scopeId) ? FetchFormat::FETCH_FORMAT_AUTO : '';
    }

    /**
     * @return string
     */
    public function getImageQuality($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->configReader->getValue(self::CONFIG_DEFAULT_QUALITY, $scope, $scopeId);
    }

    /**
     * @return string
     */
    public function getImageDpr($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->configReader->getValue(self::CONFIG_DEFAULT_DPR, $scope, $scopeId);
    }

    /**
     * @return bool
     */
    public function hasEnvironmentVariable($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return (bool)$this->configReader->getValue(self::CONFIG_PATH_ENVIRONMENT_VARIABLE, $scope, $scopeId);
    }

    /**
     * @return CloudinaryEnvironmentVariable
     */
    private function getEnvironmentVariable($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        if (is_null($this->environmentVariable)) {
            try {
                $this->environmentVariable = CloudinaryEnvironmentVariable::fromString(
                    $this->decryptor->decrypt(
                        $this->configReader->getValue(self::CONFIG_PATH_ENVIRONMENT_VARIABLE, $scope, $scopeId)
                    )
                );
            } catch (InvalidCredentials $invalidConfigException) {
                $this->logger->critical($invalidConfigException);
            }
        }
        return $this->environmentVariable;
    }

    /**
     * @return bool
     */
    public function getRemoveVersionNumber($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_REMOVE_VERSION_NUMBER, $scope, $scopeId);
    }

    /**
     * @return bool
     */
    public function getUseRootPath($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return (bool) $this->configReader->getValue(self::CONFIG_PATH_REMOVE_VERSION_NUMBER, $scope, $scopeId);
    }

    /**
     * @method getUseSecureInFrontend
     * @param  string $scope
     * @param  integer|null $scopeId
     * @return string
     */
    public function getUseSecureInFrontend($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return ($this->configReader->getValue(self::CONFIG_PATH_USE_SECURE_IN_FRONTEND, $scope, $scopeId)) ? true : false;
    }

    /**
     * @method getSecureBaseUrl
     * @param  string $path
     * @param  string $scope
     * @param  integer|null $scopeId
     * @return string
     */
    public function getSecureBaseUrl($path = "", $scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        $return = (string) $this->configReader->getValue(self::CONFIG_PATH_SECURE_BASE_URL, $scope, $scopeId);
        return rtrim($return, "/") . "/" . ltrim($path, "/");
    }

    /**
     * @method getUnsecureBaseUrl
     * @param  string $path
     * @param  string $scope
     * @param  integer|null $scopeId
     * @return string
     */
    public function getUnsecureBaseUrl($path = "", $scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        $return = (string) $this->configReader->getValue(self::CONFIG_PATH_UNSECURE_BASE_URL, $scope, $scopeId);
        return rtrim($return, "/") . "/" . ltrim($path, "/");
    }

    /**
     * @method getBaseUrl
     * @param  string $path
     * @param  string $scope
     * @param  integer|null $scopeId
     * @return string
     */
    public function getBaseUrl($path = "", $scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return ($this->getUseSecureInFrontend($scope, $scopeId)) ? $this->getSecureBaseUrl($path, $scope, $scopeId) : $this->getUnsecureBaseUrl($path, $scope, $scopeId);
    }

    /**
     * @method getMediaBaseUrl
     * @param  string $scope
     * @param  integer|null $scopeId
     * @return string
     */
    public function getMediaBaseUrl($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, $scope, $scopeId);
    }
}
