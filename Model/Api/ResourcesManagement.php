<?php

namespace Cloudinary\Cloudinary\Model\Api;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;

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
     * @var \Magento\Framework\App\Request\Http
     */
    private $_request;

    /**
     * ApiClient constructor.
     *
     * @param ConfigurationInterface $configuration
     * @param ConfigurationBuilder   $configurationBuilder
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        Api $api,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->_configuration = $configuration;
        $this->_configurationBuilder = $configurationBuilder;
        $this->_api = $api;
        $this->_request = $request;
        if ($this->_configuration->isEnabled()) {
            $this->_authorise();
        }
    }

    private function _authorise()
    {
        Cloudinary::config($this->_configurationBuilder->build());
        Cloudinary::$USER_PLATFORM = $this->_configuration->getUserPlatform();
    }

    public function _sendJsonResponse($response)
    {
        header('Content-Type: application/json');
        echo json_encode($response);
        die;
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
                $this->_request->getParam("id"), [
                "resource_type" => $this->_resourceType
                ]
            );
            $this->_sendJsonResponse(
                [
                "error" => 0,
                "data" => $this->_resourceData
                ]
            );
        } catch (\Exception $e) {
            $this->_sendJsonResponse(
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
        $this->_getResourceData();
    }

    /**
     * {@inheritdoc}
     */
    public function getVideo()
    {
        $this->_resourceType = "video";
        $this->_getResourceData();
    }
}
