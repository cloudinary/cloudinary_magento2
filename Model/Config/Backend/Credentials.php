<?php

namespace Cloudinary\Cloudinary\Model\Config\Backend;

use Cloudinary\Api\BaseApiClient;
use Cloudinary\Cloudinary;
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
     * @var Cloudinary
     */
    private $cloudinarySdk;

    /**
     * @var ConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * @var Cloudinary\Api\Admin\AdminApi
     */
    private $api;

    /**
     * Application config
     *
     * @var ScopeConfigInterface
     */
    protected $appConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

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
        ReinitableConfigInterface $appConfig,
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
        $this->appConfig = $appConfig;
        $this->encryptor = $encryptor;

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

    /**
     * @return void
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $rawValue = $this->getValue();

        parent::beforeSave();

        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->appConfig->reinit();// $decrypted = $this->encryptor->decrypt($var);

        if ($rawValue || $this->configuration->isEnabled(false)) {
            if (!$rawValue) {
                throw new ValidatorException(__(self::CREDENTIALS_CHECK_MISSING));
            }

            if ($this->isSaveAllowed()) {
                if (stripos($rawValue, 'cloudinary://') !== false) {
                    $this->validate($this->getCredentialsFromEnvironmentVariable($rawValue));
                } else {
                    $this->validate($this->getCredentialsFromEnvironmentVariable($this->encryptor->decrypt($rawValue)));
                }
            } else {
                $field = $this->configuration->getCredentials();
                $this->validate($field);
            }
        }
    }

    /**
     * @param  $credentials
     * @throws ValidatorException
     */
    private function validate($credentials)
    {
        $this->_authorise($credentials);
        $pingValidation = $this->cloudinarySdk->adminApi()->ping();
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
            // Cloudinary::config_from_url(str_replace('CLOUDINARY_URL=', '', $environmentVariable));
            $environmentVariable = str_replace('CLOUDINARY_URL=', '', $environmentVariable);
            $uri = parse_url($environmentVariable);
            if (!isset($uri["scheme"]) || strtolower($uri["scheme"]) !== "cloudinary") {
                throw new InvalidArgumentException("Invalid CLOUDINARY_URL scheme. Expecting to start with 'cloudinary://'");
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
            return $this->getCredentialsFromEnvironmentVariable($this->cloudinarySdk->toString());
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
     * @param $credentials
     */
    private function _authorise($credentials)
    {
        $this->cloudinarySdk = new Cloudinary($credentials);
        BaseApiClient::$userPlatform =  $this->configuration->getUserPlatform();
        return $this;
    }
}
