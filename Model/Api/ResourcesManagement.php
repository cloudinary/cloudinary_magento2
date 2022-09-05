<?php

namespace Cloudinary\Cloudinary\Model\Api;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Json\EncoderInterface;

class ResourcesManagement implements \Cloudinary\Cloudinary\Api\ResourcesManagementInterface
{
    private $initialized;
    private $id;
    private $maxResults;
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
     * @var Cloudinary\Api\Admin\AdminApi
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
     * @param AdminApi               $api
     * @param Http                   $request
     * @param EncoderInterface       $jsonEncoder
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        AdminApi $api,
        Http $request,
        EncoderInterface $jsonEncoder
    ) {
        $this->_configuration = $configuration;
        $this->_configurationBuilder = $configurationBuilder;
        $this->_api = $api;
        $this->_request = $request;
        $this->_jsonEncoder = $jsonEncoder;
    }

    private function initialize()
    {
        if (!$this->initialized) {
            $this->initialized = true;
            if (($id = $this->_request->getParam("id"))) {
                $this->setId(\rawurldecode($id));
            }
            if (($maxResults = $this->_request->getParam("max_results"))) {
                $this->setMaxResults($maxResults);
            }
            if ($this->_configuration->isEnabled()) {
                Cloudinary::config($this->_configurationBuilder->build());
                Cloudinary::$USER_PLATFORM = $this->_configuration->getUserPlatform();
            }
        }
        return $this;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
        return $this;
    }

    public function getMaxResults()
    {
        return $this->maxResults;
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
            $this->initialize();
            $this->_resourceData = $this->_api->resource(
                $this->getId(),
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

    /**
     * {@inheritdoc}
     */
    public function getResourcesByTag()
    {
        try {
            $this->initialize();
            $resources = $this->_api->resources_by_tag(
                $this->getId(),
                [
                    "resource_type" => $this->_resourceType,
                    "max_results" => (int) $this->maxResults || null
                ]
            )['resources'];
            return $this->_jsonEncoder->encode(
                [
                "error" => 0,
                "data" => $resources
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
}
