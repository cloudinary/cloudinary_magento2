<?php

namespace Cloudinary\Cloudinary\Core;

use Cloudinary;
use Cloudinary\Cloudinary\Core\Exception\ApiError;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Model\MediaLibraryMapFactory;
use Cloudinary\Uploader;
use Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;

class CloudinaryImageProvider implements ImageProvider
{
    /**
     * @var bool
     */
    private $_authorised;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var UploadResponseValidator
     */
    private $uploadResponseValidator;

    /**
     * @var ConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * @var ProductMediaConfig
     */
    private $productMediaConfig;

    /**
     * @var MediaLibraryMapFactory
     */
    private $mediaLibraryMapFactory;

    /**
     * @method __construct
     * @param  ConfigurationInterface  $configuration
     * @param  ConfigurationBuilder    $configurationBuilder
     * @param  UploadResponseValidator $uploadResponseValidator
     * @param  ProductMediaConfig      $productMediaConfig
     * @param  MediaLibraryMapFactory  $mediaLibraryMapFactory
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        UploadResponseValidator $uploadResponseValidator,
        ProductMediaConfig $productMediaConfig,
        MediaLibraryMapFactory $mediaLibraryMapFactory
    ) {
        $this->configuration = $configuration;
        $this->uploadResponseValidator = $uploadResponseValidator;
        $this->configurationBuilder = $configurationBuilder;
        $this->productMediaConfig = $productMediaConfig;
        $this->mediaLibraryMapFactory = $mediaLibraryMapFactory;
    }

    /**
     * @param  ConfigurationInterface $configuration
     * @return CloudinaryImageProvider
     */
    public static function fromConfiguration(ConfigurationInterface $configuration)
    {
        return new CloudinaryImageProvider(
            $configuration,
            new ConfigurationBuilder($configuration),
            new UploadResponseValidator()
        );
    }

    /**
     * @param  Image $image
     * @return mixed
     */
    public function upload(Image $image)
    {
        $this->authorise();
        if (!$this->configuration->isEnabled()) {
            return false;
        }

        try {
            $uploadResult = Uploader::upload(
                (string)$image,
                $this->configuration->getUploadConfig()->toArray() + [ "folder" => $image->getRelativeFolder()]
            );
            return $this->uploadResponseValidator->validateResponse($image, $uploadResult);
        } catch (\Exception $e) {
            ApiError::throwWith($image, $e->getMessage());
        }
    }

    /**
     * @param  Image          $image
     * @param  Transformation $transformation
     * @return Image
     */
    public function retrieveTransformed(Image $image, Transformation $transformation)
    {
        $this->authorise();
        $imageId = $image->getId();

        if ($this->configuration->isEnabledLocalMapping()) {
            //Look for a match on the mapping table:
            preg_match('/(CLD_[A-Za-z0-9]{13}_).+$/', $imageId, $cldUniqid);
            if ($cldUniqid && isset($cldUniqid[1])) {
                $mapped = $this->mediaLibraryMapFactory->create()->getCollection()->addFieldToFilter("cld_uniqid", $cldUniqid[1])->setPageSize(1)->getFirstItem();
                if ($mapped && ($origPublicId = $mapped->getCldPublicId())) {
                    if (($freeTransformation = $mapped->getFreeTransformation()) && \strpos($imageId, $this->productMediaConfig->getBaseMediaUrl()) === 0) {
                        $transformation->withFreeform($freeTransformation, false);
                    }
                    $imageId = $origPublicId;
                }
            }
        }

        //Generate the CLD URL:
        $imagePath = \cloudinary_url(
            $imageId,
            [
            'transformation' => $transformation->build(),
            'secure' => true,
            'sign_url' => $this->configuration->getUseSignedUrls(),
            'version' => 1
            ]
        );

        if (!$this->configuration->isEnabledProductGallery()) {
            //Handle with use-root-path if necessary:
            if ($this->configuration->getUseRootPath()) {
                if (\strpos($imagePath, "cloudinary.com/{$this->configuration->getCloud()}/image/upload/") !== false) {
                    $imagePath = str_replace("cloudinary.com/{$this->configuration->getCloud()}/image/upload/", "cloudinary.com/{$this->configuration->getCloud()}/", $imagePath);
                } elseif (\strpos($imagePath, "cloudinary.com/image/upload/") !== false) {
                    $imagePath = str_replace("cloudinary.com/image/upload/", "cloudinary.com/", $imagePath);
                }
            }

            //Remove version number if necessary:
            if ($this->configuration->getRemoveVersionNumber()) {
                $regex = '/\/v[0-9]{1,10}\/' . preg_quote(ltrim($imageId, '/'), '/') . '$/';
                $imagePath = preg_replace($regex, '/' . ltrim($imageId, '/'), $imagePath);
            }
        }

        return Image::fromPath($imagePath, $image->getRelativePath());
    }

    /**
     * @param  Image $image
     * @return Image
     */
    public function retrieve(Image $image)
    {
        $this->authorise();
        return $this->retrieveTransformed($image, $this->configuration->getDefaultTransformation());
    }

    /**
     * @param  Image $image
     * @return bool
     */
    public function delete(Image $image)
    {
        $this->authorise();
        if ($this->configuration->isEnabled()) {
            Uploader::destroy($image->getIdWithoutExtension());
        }
    }

    /**
     * @return bool
     */
    public function validateCredentials()
    {
        try {
            $this->authorise();
            $pingValidation = $this->api->ping();
            if (!(isset($pingValidation["status"]) && $pingValidation["status"] === "ok")) {
                return false;
                //throw new ValidatorException(__(self::CREDENTIALS_CHECK_UNSURE));
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function authorise()
    {
        if (!$this->_authorised && $this->configuration->isEnabled()) {
            Cloudinary::config($this->configurationBuilder->build());
            Cloudinary::$USER_PLATFORM = $this->configuration->getUserPlatform();
            $this->_authorised = true;
        }
    }
}
