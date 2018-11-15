<?php

namespace Cloudinary\Cloudinary\Model\Observer;

use Cloudinary\Cloudinary\Core\AutoUploadMapping\RequestProcessor;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;

class Configuration implements ObserverInterface
{
    const AUTO_UPLOAD_SETUP_FAIL_MESSAGE = 'Error. Unable to setup auto upload mapping.';

    /**
     * @var RequestProcessor
     */
    protected $requestProcessor;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Cloudinary\Cloudinary\Model\Configuration
     */
    protected $configuration;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    protected $scope = ScopeInterface::SCOPE_STORE;
    protected $scopeId = null;
    protected $changedPaths = [];

    /**
     * @param RequestProcessor $requestProcessor
     * @param ManagerInterface $messageManager
     * @param \Cloudinary\Cloudinary\Model\Configuration $configuration
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        RequestProcessor $requestProcessor,
        ManagerInterface $messageManager,
        \Cloudinary\Cloudinary\Model\Configuration $configuration,
        TypeListInterface $cacheTypeList
    ) {
        $this->requestProcessor = $requestProcessor;
        $this->messageManager = $messageManager;
        $this->configuration = $configuration;
        $this->cacheTypeList = $cacheTypeList;
    }

    protected function _init(Observer $observer)
    {
        //Clear config cache if needed
        $this->changedPaths = (array) $observer->getEvent()->getChangedPaths();
        if (in_array($this->changedPaths, [
            \Cloudinary\Cloudinary\Model\Configuration::CONFIG_PATH_ENABLED,
            \Cloudinary\Cloudinary\Model\Configuration::CONFIG_PATH_ENVIRONMENT_VARIABLE,
            \Cloudinary\Cloudinary\Model\AutoUploadMapping\AutoUploadConfiguration::REQUEST_PATH
        ])) {
            $this->cleanConfigCache();
        }

        //Get current configuration scope from request & inject to requestProcessor.
        $this->scopeId = $observer->getEvent()->getStore();
        $this->scope = ScopeInterface::SCOPE_STORE;
        if (!$this->scopeId && ($this->scopeId = $observer->getEvent()->getWebsite())) {
            $this->scope = ScopeInterface::SCOPE_WEBSITE;
        }
        if (!$this->scopeId) {
            $this->scope = \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT;
        }
        $this->requestProcessor->setScope($this->scope)->setScopeId($this->scopeId);
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $this->_init($observer);

        if (!$this->requestProcessor->handle('media', $this->configuration->getMediaBaseUrl($this->scope, $this->scopeId))) {
            $this->messageManager->addErrorMessage(self::AUTO_UPLOAD_SETUP_FAIL_MESSAGE);
        }
    }

    protected function cleanConfigCache()
    {
        $this->_cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->_cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
        return $this;
    }
}
