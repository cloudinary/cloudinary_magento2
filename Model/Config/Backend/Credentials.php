<?php

namespace Cloudinary\Cloudinary\Model\Config\Backend;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Exception\InvalidCredentials;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Credentials extends Encrypted
{
    const CREDENTIALS_CHECK_MISSING = 'You must provide Cloudinary credentials.';
    const CREDENTIALS_CHECK_FAILED = 'Your Cloudinary credentials are not correct.';
    const CREDENTIALS_CHECK_UNSURE = 'There was a problem validating your Cloudinary credentials.';
    const CLOUDINARY_ENABLED_PATH = 'groups/cloud/fields/cloudinary_enabled/value';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * @var Cloudinary\Api
     */
    private $api;

    /**
     * Application config
     *
     * @var ScopeConfigInterface
     */
    protected $appConfig;

    /**
     * @param Context                   $context
     * @param Registry                  $registry
     * @param ScopeConfigInterface      $config
     * @param TypeListInterface         $cacheTypeList
     * @param EncryptorInterface        $encryptor
     * @param ConfigurationInterface    $configuration
     * @param AbstractResource          $resource
     * @param AbstractDb                $resourceCollection
     * @param ConfigurationBuilder      $configurationBuilder
     * @param Api                       $api
     * @param ReinitableConfigInterface $appConfig
     * @param array                     $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor,
        ConfigurationInterface $configuration,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        ConfigurationBuilder $configurationBuilder,
        Api $api,
        ReinitableConfigInterface $appConfig,
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
        $this->api = $api;
        $this->appConfig = $appConfig;

        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $encryptor,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function beforeSave()
    {
        $rawValue = $this->getValue();

        parent::beforeSave();

        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->appConfig->reinit();

        if ($rawValue || $this->configuration->isEnabled(false)) {
            if (!$rawValue) {
                throw new ValidatorException(__(self::CREDENTIALS_CHECK_MISSING));
            }

            if ($this->isSaveAllowed()) {
                $this->validate($this->getCredentialsFromEnvironmentVariable($rawValue));
            } else {
                $this->validate($this->getCredentialsFromConfig());
            }
        }
    }

    /**
     * @param  array $credentials
     * @throws ValidatorException
     */
    private function validate(array $credentials)
    {
        $this->_authorise($credentials);
        $pingValidation = $this->api->ping();
        if (!(isset($pingValidation["status"]) && $pingValidation["status"] === "ok")) {
            throw new ValidatorException(__(self::CREDENTIALS_CHECK_UNSURE));
        }
    }

    /**
     * @param  string $environmentVariable
     * @throws ValidatorException
     * @return array
     */
    private function getCredentialsFromEnvironmentVariable($environmentVariable)
    {
        try {
            Cloudinary::config_from_url(str_replace('CLOUDINARY_URL=', '', $environmentVariable));
            $credentials = [
                "cloud_name" => Cloudinary::config_get('cloud_name'),
                "api_key" => Cloudinary::config_get('api_key'),
                "api_secret" => Cloudinary::config_get('api_secret')
            ];
            if (Cloudinary::config_get('private_cdn')) {
                $credentials["private_cdn"] = Cloudinary::config_get('private_cdn');
            }

            return $credentials;
        } catch (\Exception $e) {
            throw new ValidatorException(__(self::CREDENTIALS_CHECK_FAILED));
        }
    }

    /**
     * @throws ValidatorException
     * @return array
     */
    private function getCredentialsFromConfig()
    {
        try {
            return $this->getCredentialsFromEnvironmentVariable($this->configuration->getEnvironmentVariable()->__toString());
        } catch (InvalidCredentials $e) {
            throw new ValidatorException(__(self::CREDENTIALS_CHECK_FAILED));
        }
    }

    /**
     * @return bool
     */
    private function isModuleActiveInFormData()
    {
        return $this->getDataByPath(self::CLOUDINARY_ENABLED_PATH) === '1';
    }

    /**
     * @param array $credentials
     */
    private function _authorise(array $credentials)
    {
        Cloudinary::config($credentials);
        Cloudinary::$USER_PLATFORM = $this->configuration->getUserPlatform();
    }
}
