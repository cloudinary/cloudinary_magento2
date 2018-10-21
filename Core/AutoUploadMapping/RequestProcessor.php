<?php

namespace Cloudinary\Cloudinary\Core\AutoUploadMapping;

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
     * @param string $folder
     * @param string $url
     * @return bool
     */
    public function handle($folder, $url)
    {
        if ($this->configuration->isActive() == $this->configuration->getRequestState()) {
            return true;
        }

        if ($this->configuration->getRequestState() == AutoUploadConfigurationInterface::ACTIVE) {
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
            $this->configuration->setState(AutoUploadConfigurationInterface::ACTIVE);
        } else {
            $this->configuration->setRequestState(AutoUploadConfigurationInterface::INACTIVE);
        }

        return $result;
    }
}
