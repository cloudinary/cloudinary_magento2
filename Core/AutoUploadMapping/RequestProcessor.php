<?php

namespace Cloudinary\Cloudinary\Core\AutoUploadMapping;

use Magento\Store\Model\ScopeInterface;

class RequestProcessor
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var ApiClient
     */
    private $apiClient;

    protected $scope = ScopeInterface::SCOPE_STORE;

    protected $scopeId = null;

    /**
     * @param AutoUploadConfigurationInterface $configuration
     * @param ApiClient $apiClient
     */
    public function __construct(
        AutoUploadConfigurationInterface $configuration,
        ApiClient $apiClient
    ) {
        $this->configuration = $configuration;
        $this->apiClient = $apiClient;
    }

    /**
     * @method setScopeId
     * @param  string|null  $scopeId
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @method getScope
     * @return string|null
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @method setScopeId
     * @param  integer|null  $scopeId
     * @return $this
     */
    public function setScopeId($scopeId)
    {
        $this->scopeId = $scopeId;
        return $this;
    }

    /**
     * @method getScopeId
     * @return integer|null
     */
    public function getScopeId($scope)
    {
        return $this->scopeId;
    }

    /**
     * @param string $folder
     * @param string $url
     * @return bool
     */
    public function handle($folder, $url)
    {
        if ($this->configuration->isActive($this->scope, $this->scopeId) == $this->configuration->getRequestState($this->scope, $this->scopeId)) {
            return true;
        }

        if ($this->configuration->getRequestState($this->scope, $this->scopeId) == AutoUploadConfigurationInterface::ACTIVE) {
            return $this->handleActiveRequest($folder, $url);
        }

        $this->configuration->setState(AutoUploadConfigurationInterface::INACTIVE);

        return true;
    }

    /**
     * @param string $folder
     * @param string $url
     * @return bool
     */
    private function handleActiveRequest($folder, $url)
    {
        $result = $this->apiClient->prepareMapping($folder, $url);

        if ($result) {
            $this->configuration->setState(AutoUploadConfigurationInterface::ACTIVE, $this->scope, $this->scopeId);
        } else {
            $this->configuration->setRequestState(AutoUploadConfigurationInterface::INACTIVE, $this->scope, $this->scopeId);
        }

        return $result;
    }
}
