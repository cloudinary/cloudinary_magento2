<?php

namespace Cloudinary\Cloudinary\Model\AutoUploadMapping;

use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

class AutoUploadConfiguration implements AutoUploadConfigurationInterface
{
    const STATE_PATH = 'cloudinary/configuration/cloudinary_auto_upload_mapping_state';
    const REQUEST_PATH = 'cloudinary/configuration/cloudinary_auto_upload_mapping_request';
    const CONFIG_TRUE = '1';
    const CONFIG_FALSE = '0';

    /**
     * @var ScopeConfigInterface
     */
    private $configReader;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param ScopeConfigInterface $configReader
     * @param WriterInterface      $configWriter
     */
    public function __construct(
        ScopeConfigInterface $configReader,
        WriterInterface $configWriter
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
    }

    /**
     * @return bool
     */
    public function isActive($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->configReader->isSetFlag(self::STATE_PATH, $scope, $scopeId);
    }

    /**
     * @param bool $state
     */
    public function setState($state, $scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        $this->setFlag(self::STATE_PATH, $state, $scope, $scopeId);
    }

    /**
     * @return bool
     */
    public function getRequestState($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->configReader->isSetFlag(self::REQUEST_PATH, $scope, $scopeId);
    }

    /**
     * @param bool $state
     */
    public function setRequestState($state, $scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        $this->setFlag(self::REQUEST_PATH, $state, $scope, $scopeId);
    }

    /**
     * @param string $key
     * @param bool $state
     */
    private function setFlag($key, $state, $scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        $this->configWriter->save($key, $state ? self::CONFIG_TRUE : self::CONFIG_FALSE, $scope, $scopeId);
    }
}
