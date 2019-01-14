<?php

namespace Cloudinary\Cloudinary\Model\Api;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Json\EncoderInterface;

class ResourcesManagement implements \Cloudinary\Cloudinary\Api\ResourcesManagementInterface
{
    protected $_resourceType = "image";
    protected $_resourceData = [];

    /**
     * @var ConfigurationInterface
     */
    private $_configuration;

    /**
     * @var ConfigurationBuilder
     */
    private $_configurationBuilder;

    /**
     * @var Cloudinary\Api
     */
    private $_api;

    /**
     * @var Http
     */
    private $_request;

    /**
     * @var EncoderInterface
     */
    private $_jsonEncoder;

    /**
     * ApiClient constructor.
     *
     * @param ConfigurationInterface $configuration
     * @param ConfigurationBuilder   $configurationBuilder
     * @param Api                    $api
     * @param Http                   $request
     * @param EncoderInterface       $jsonEncoder
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        Api $api,
        Http $request,
        EncoderInterface $jsonEncoder
    ) {
        $this->_configuration = $configuration;
        $this->_configurationBuilder = $configurationBuilder;
        $this->_api = $api;
        $this->_request = $request;
        $this->_jsonEncoder = $jsonEncoder;
        if ($this->_configuration->isEnabled()) {
            $this->_authorise();
        }
    }

    private function _authorise()
    {
        Cloudinary::config($this->_configurationBuilder->build());
        Cloudinary::$USER_PLATFORM = $this->_configuration->getUserPlatform();
    }

    /**
     * Get details of a single resource
     *
     * @method _getResourceData
     * @return string (json encoded data)
     */
    protected function _getResourceData()
    {
        try {
            $this->_resourceData = $this->_api->resource(
                $this->_request->getParam("id"),
                [
                "resource_type" => $this->_resourceType
                ]
            );
            return $this->_jsonEncoder->encode(
                [
                "error" => 0,
                "data" => $this->_resourceData
                ]
            );
        } catch (\Exception $e) {
            return $this->_jsonEncoder->encode(
                [
                "error" => 1,
                "message" => $e->getMessage()
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getImage()
    {
        $this->_resourceType = "image";
        return $this->_getResourceData();
    }

    /**
     * {@inheritdoc}
     */
    public function getVideo()
    {
        $this->_resourceType = "video";
        return $this->_getResourceData();
    }
}
