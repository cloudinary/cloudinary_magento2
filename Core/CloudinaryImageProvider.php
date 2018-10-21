<?php

namespace Cloudinary\Cloudinary\Core;

use Cloudinary;
use Cloudinary\Uploader;
use Cloudinary\Cloudinary\Core\Exception\ApiError;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Security;
use Cloudinary\Cloudinary\Core\Image\Transformation\Format;
use Cloudinary\Cloudinary\Core\Image\Transformation\FetchFormat;

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
     * @var CredentialValidator
     */
    private $credentialValidator;

    /**
     * @param ConfigurationInterface $configuration
     * @param ConfigurationBuilder $configurationBuilder
     * @param UploadResponseValidator $uploadResponseValidator
     * @param CredentialValidator $credentialValidator
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        UploadResponseValidator $uploadResponseValidator,
        CredentialValidator $credentialValidator
    ) {
        $this->configuration = $configuration;
        $this->uploadResponseValidator = $uploadResponseValidator;
        $this->configurationBuilder = $configurationBuilder;
        $this->credentialValidator = $credentialValidator;
        if ($configuration->isEnabled()) {
            $this->authorise();
        }
    }

    /**
     * @param ConfigurationInterface $configuration
     * @return CloudinaryImageProvider
     */
    public static function fromConfiguration(ConfigurationInterface $configuration){
        return new CloudinaryImageProvider(
            $configuration,
            new ConfigurationBuilder($configuration),
            new UploadResponseValidator(),
            new CredentialValidator()
        );
    }

    /**
     * @param Image $image
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
     * @param Image $image
     * @param Transformation $transformation
     * @return Image
     */
    public function retrieveTransformed(Image $image, Transformation $transformation)
    {
        return Image::fromPath(
            \cloudinary_url($image->getId(), ['transformation' => $transformation->build(), 'secure' => true]),
            $image->getRelativePath()
        );
    }

    /**
     * @param Image $image
     * @return Image
     */
    public function retrieve(Image $image)
    {
        return $this->retrieveTransformed($image, $this->configuration->getDefaultTransformation());
    }

    /**
     * @param Image $image
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
        return $this->credentialValidator->validate($this->configuration->getCredentials());
    }

    private function authorise()
    {
        Cloudinary::config($this->configurationBuilder->build());
        Cloudinary::$USER_PLATFORM = $this->configuration->getUserPlatform();
    }
}
