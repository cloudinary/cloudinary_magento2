<?php

namespace Cloudinary\Cloudinary\Api;

interface ResourcesManagementInterface
{

    /**
     * GET for getImage api
     *
     * @return string
     */
    public function getImage();

    /**
     * GET for getVideo api
     *
     * @return string
     */
    public function getVideo();

    /**
     * GET for getSpinestFirstImage api
     *
     * @return string
     */
    public function getResourcesByTag();
}
