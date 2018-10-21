<?php

namespace Cloudinary\Cloudinary\Model\Observer;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Cloudinary\Cloudinary\Core\AutoUploadMapping\RequestProcessor;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class Configuration implements ObserverInterface
{
    const AUTO_UPLOAD_SETUP_FAIL_MESSAGE = 'Error. Unable to setup auto upload mapping.';

    /**
     * @var RequestProcessor
     */
    private $requestProcessor;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param RequestProcessor $requestProcessor
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        RequestProcessor $requestProcessor,
        ManagerInterface $messageManager
    ) {
        $this->requestProcessor = $requestProcessor;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->requestProcessor->handle('media', $this->getMediaBaseUrl())) {
            $this->messageManager->addErrorMessage(self::AUTO_UPLOAD_SETUP_FAIL_MESSAGE);
        }
    }

    /**
     * @return string
     */
    function getMediaBaseUrl() {
        /** @var \Magento\Framework\ObjectManagerInterface $om */
        $om = ObjectManager::getInstance();

        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $om->get('Magento\Store\Model\StoreManagerInterface');

        /** @var \Magento\Store\Api\Data\StoreInterface|\Magento\Store\Model\Store $currentStore */
        $currentStore = $storeManager->getStore();

        return $currentStore->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }
}
