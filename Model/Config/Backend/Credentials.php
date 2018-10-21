<?php

namespace Cloudinary\Cloudinary\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\ValidatorException;
use Cloudinary\Cloudinary\Core\CredentialValidator;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Security\CloudinaryEnvironmentVariable;
use Cloudinary\Cloudinary\Core\Credentials as CredentialsValue;
use Cloudinary\Cloudinary\Core\Exception\InvalidCredentials;
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
     * @var CredentialValidator
     */
    private $credentialValidator;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     * @param CredentialValidator $credentialValidator
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
        CredentialValidator $credentialValidator,
        ConfigurationInterface $configuration,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->credentialValidator = $credentialValidator;
        $this->configuration = $configuration;

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
        if (!$this->credentialValidator->validate($credentials)) {
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
}
