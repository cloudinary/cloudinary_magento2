<?php

namespace Cloudinary\Cloudinary\Core;

use Cloudinary\Cloudinary\Core\Image\Transformation;

interface ConfigurationInterface
{
    /**
     * @return Cloud
     */
    public function getCloud();

    /**
     * @return Credentials
     */
    public function getCredentials();

    /**
     * @return Transformation
     */
    public function getDefaultTransformation();

    /**
     * This feature is deprecated and will be removed in the next major version.
     *
     * @deprecated will be removed in the next major version.
     * @return boolean
     */
    public function getCdnSubdomainStatus();

    /**
     * @return string
     */
    public function getUserPlatform();

    /**
     * @return UploadConfig
     */
    public function getUploadConfig();

    /**
     * @return boolean
     */
    public function isEnabled();

    /**
     * @return array
     */
    public function getFormatsToPreserve();

    /**
     * @param string $file
     *
     * @return string
     */
    public function getMigratedPath($file);

    /**
     * @return void
     */
    public function enable();

    /**
     * @return void
     */
    public function disable();
}
