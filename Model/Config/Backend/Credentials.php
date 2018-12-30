<?php

namespace Cloudinary\Cloudinary\Model\Config\Backend;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Credentials as CredentialsValue;
use Cloudinary\Cloudinary\Core\Exception\InvalidCredentials;
use Cloudinary\Cloudinary\Core\Security\CloudinaryEnvironmentVariable;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
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
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     * @param ConfigurationInterface $configuration
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
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
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
        $this->api = $api;

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

        if (!$rawValue) {
            throw new ValidatorException(__(self::CREDENTIALS_CHECK_MISSING));
        }

        if ($this->isSaveAllowed()) {
            $this->validate($this->getCredentialsFromEnvironmentVariable($rawValue));
        } else {
            $this->validate($this->getCredentialsFromConfig());
        }
    }

    /**
     * @param CredentialsValue $credentials
     * @throws ValidatorException
     */
    private function validate(CredentialsValue $credentials)
    {
        $this->_authorise();
        $pingValidation = $this->api->ping();
        if (!(isset($pingValidation["status"]) && $pingValidation["status"] === "ok")) {
            throw new ValidatorException(__(self::CREDENTIALS_CHECK_UNSURE));
        }
    }

    /**
     * @param string $environmentVariable
     * @throws ValidatorException
     * @return CredentialsValue
     */
    private function getCredentialsFromEnvironmentVariable($environmentVariable)
    {
        try {
            return CloudinaryEnvironmentVariable::fromString($environmentVariable)->getCredentials();
        } catch (InvalidCredentials $e) {
            throw new ValidatorException(__(self::CREDENTIALS_CHECK_FAILED));
        }
    }

    /**
     * @throws ValidatorException
     * @return CredentialsValue
     */
    private function getCredentialsFromConfig()
    {
        try {
            return $this->configuration->getCredentials();
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

    private function _authorise()
    {
        Cloudinary::config($this->configurationBuilder->build());
        Cloudinary::$USER_PLATFORM = $this->configuration->getUserPlatform();
    }
}
