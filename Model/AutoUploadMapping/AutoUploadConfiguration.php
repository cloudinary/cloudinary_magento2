<?php

namespace Cloudinary\Cloudinary\Model\AutoUploadMapping;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;

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
    public function isActive()
    {
        return $this->configReader->isSetFlag(self::STATE_PATH);
    }

    /**
     * @param bool $state
     */
    public function setState($state)
    {
        $this->setFlag(self::STATE_PATH, $state);
    }

    /**
     * @return bool
     */
    public function getRequestState()
    {
        return $this->configReader->isSetFlag(self::REQUEST_PATH);
    }

    /**
     * @param bool $state
     */
    public function setRequestState($state)
    {
        $this->setFlag(self::REQUEST_PATH, $state);
    }

    /**
     * @param string $key
     * @param bool $state
     */
    private function setFlag($key, $state)
    {
        $this->configWriter->save($key, $state ? self::CONFIG_TRUE : self::CONFIG_FALSE);
    }
}
