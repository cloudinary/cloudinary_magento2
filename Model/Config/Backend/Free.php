<?php

namespace Cloudinary\Cloudinary\Model\Config\Backend;

use Cloudinary\Cloudinary\Core\CloudinaryImageProvider;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\Freeform;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Zend_Http_Response;

class Free extends \Magento\Framework\App\Config\Value
{
    const ERROR_FORMAT = 'Incorrect custom transform - %1';
    const ERROR_DEFAULT = 'please update';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var CloudinaryImageProvider
     */
    private $cloudinaryImageProvider;

    /**
     * @var ZendClient
     */
    private $zendClient;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ConfigurationInterface $configuration,
     * @param CloudinaryImageProvider $cloudinaryImageProvider
     * @param ZendClient $zendClient
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ConfigurationInterface $configuration,
        CloudinaryImageProvider $cloudinaryImageProvider,
        ZendClient $zendClient,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->cloudinaryImageProvider = $cloudinaryImageProvider;
        $this->zendClient = $zendClient;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        if ($this->hasAccountConfigured() && $this->getValue()) {

            $transform = $this->configuration
                ->getDefaultTransformation()
                ->withFreeform(Freeform::fromString($this->getValue()));

            $this->validate($this->sampleImageUrl($transform));

        }

        parent::beforeSave();
    }

    /**
     * @param string $url
     * @throws ValidatorException
     */
    public function validate($url)
    {
        $response = null;

        try {
            $response = $this->httpRequest($url);
        } catch (\Exception $e) {
            throw new ValidatorException(__(self::ERROR_FORMAT, self::ERROR_DEFAULT));
        }

        if ($response->isError()) {
            throw new ValidatorException($this->formatError($response));
        }
    }

    /**
     * @param Zend_Http_Response $response
     * @return Phrase
     */
    public function formatError(Zend_Http_Response $response)
    {
        return __(
            self::ERROR_FORMAT,
            $response->getStatus() == 400 ? $response->getHeader('x-cld-error') : self::ERROR_DEFAULT
        );
    }

    /**
     * @param string $url
     * @return Zend_Http_Response
     */
    public function httpRequest($url)
    {
        return $this->zendClient->setUri($url)->request(ZendClient::GET);
    }

    /**
     * @return bool
     */
    public function hasAccountConfigured()
    {
        return  $this->configuration->isEnabled() && ((string)$this->configuration->getCloud() !== '');
    }

    /**
     * @param Transformation $transformation
     * @return string
     */
    public function sampleImageUrl(Transformation $transformation)
    {
        return (string)$this->cloudinaryImageProvider->retrieveTransformed(
            Image::fromPath('sample.jpg'),
            $transformation
        );
    }

    /**
     * @param String $filename
     * @param Transformation $transformation
     * @return string
     */
    public function namedImageUrl($filename, Transformation $transformation)
    {
        if (empty($filename)) {
            throw new \RuntimeException('Error: missing image identifier');
        }

        return (string)$this->cloudinaryImageProvider->retrieveTransformed(
            Image::fromPath(
                $filename,
                $this->configuration->getMigratedPath(sprintf('catalog/product/%s', $filename))
            ),
            $transformation
        );
    }
}
