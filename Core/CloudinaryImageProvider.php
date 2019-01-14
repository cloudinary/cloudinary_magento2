<?php

namespace Cloudinary\Cloudinary\Core;

use Cloudinary;
use Cloudinary\Cloudinary\Core\Exception\ApiError;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Uploader;

class CloudinaryImageProvider implements ImageProvider
{
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
     * @param ConfigurationInterface  $configuration
     * @param ConfigurationBuilder    $configurationBuilder
     * @param UploadResponseValidator $uploadResponseValidator
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        UploadResponseValidator $uploadResponseValidator
    ) {
        $this->configuration = $configuration;
        $this->uploadResponseValidator = $uploadResponseValidator;
        $this->configurationBuilder = $configurationBuilder;
        if ($configuration->isEnabled()) {
            $this->authorise();
        }
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
        $imagePath = \cloudinary_url(
            $image->getId(),
            [
            'transformation' => $transformation->build(),
            'secure' => true,
            'sign_url' => $this->configuration->getUseSignedUrls()
            ]
        );

        if ($this->configuration->getUseRootPath()) {
            if (strpos($imagePath, "cloudinary.com/{$this->configuration->getCloud()}/image/upload/") !== false) {
                $imagePath = str_replace("cloudinary.com/{$this->configuration->getCloud()}/image/upload/", "cloudinary.com/{$this->configuration->getCloud()}/", $imagePath);
            } elseif (strpos($imagePath, "cloudinary.com/image/upload/") !== false) {
                $imagePath = str_replace("cloudinary.com/image/upload/", "cloudinary.com/", $imagePath);
            }
        }

        if ($this->configuration->getRemoveVersionNumber()) {
            $regex = '/\/v[0-9]+\/' . preg_quote(ltrim($image->getId(), '/'), '/') . '$/';
            $imagePath = preg_replace($regex, '/' . ltrim($image->getId(), '/'), $imagePath);
        }

        return Image::fromPath($imagePath, $image->getRelativePath());
    }

    /**
     * @param  Image $image
     * @return Image
     */
    public function retrieve(Image $image)
    {
        return $this->retrieveTransformed($image, $this->configuration->getDefaultTransformation());
    }

    /**
     * @param  Image $image
     * @return bool
     */
    public function delete(Image $image)
    {
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
        Cloudinary::config($this->configurationBuilder->build());
        Cloudinary::$USER_PLATFORM = $this->configuration->getUserPlatform();
    }
}
