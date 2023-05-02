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
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Laminas\Http\Response as LaminasResponse;


class Free extends \Magento\Framework\App\Config\Value
{
    const ERROR_FORMAT = 'Incorrect custom transform - %1';
    const ERROR_DEFAULT = 'please update';

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var CloudinaryImageProvider
     */
    private $cloudinaryImageProvider;

    /**
     * @var LaminasClient|\Magento\Framework\HTTP\ZendClient
     */
    private $httpClient;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ConfigurationInterface $configuration
     * @param CloudinaryImageProvider $cloudinaryImageProvider
     * @param ProductMetadataInterface $productMetadata
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ConfigurationInterface $configuration,
        CloudinaryImageProvider $cloudinaryImageProvider,
        ProductMetadataInterface $productMetadata,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->cloudinaryImageProvider = $cloudinaryImageProvider;
        // fix for magento versions 2.4.6 or newer
        if (version_compare($productMetadata->getVersion(), '2.4.6', '>=')) {
            $this->httpClient = new \Magento\Framework\HTTP\LaminasClient();
        } else {
            $this->httpClient = new \Magento\Framework\HTTP\ZendClient();
        }


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
     * @param  string $url
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
     * @param  LaminasResponse $response
     * @return Phrase
     */
    public function formatError(LaminasResponse $response)
    {
        return __(
            self::ERROR_FORMAT,
            $response->getStatus() == 400 ? $response->getHeader('x-cld-error') : self::ERROR_DEFAULT
        );
    }

    /**
     * @param $url
     * @return mixed
     */
    public function httpRequest($url)
    {

        return $this->httpClient->setUri($url)->request(\Laminas\Http\Request::METHOD_GET);
    }

    /**
     * @return bool
     */
    public function hasAccountConfigured()
    {
        return  $this->configuration->isEnabled() && ((string)$this->configuration->getCloud() !== '');
    }

    /**
     * @param  Transformation $transformation
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
     * @param  String         $filename
     * @param  Transformation $transformation
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
