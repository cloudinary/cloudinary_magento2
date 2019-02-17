<?php

namespace Cloudinary\Cloudinary\Controller\Adminhtml\Cms\Wysiwyg\Images;

use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryResolver;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;

/**
 * Upload image.
 */
class Upload extends \Magento\Cms\Controller\Adminhtml\Wysiwyg\Images\Upload
{
    /**
     * @var MediaLibraryHelper
     */
    protected $mediaLibraryHelper;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JsonFactory $resultJsonFactory
     * @param DirectoryResolver|null $directoryResolver
     * @param MediaLibraryHelper $mediaLibraryHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JsonFactory $resultJsonFactory,
        DirectoryResolver $directoryResolver = null,
        MediaLibraryHelper $mediaLibraryHelper
    ) {
        parent::__construct($context, $coreRegistry, $resultJsonFactory, $directoryResolver);
        $this->mediaLibraryHelper = $mediaLibraryHelper;
    }

    /**
     * Files upload processing.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $tmpfile = $this->mediaLibraryHelper->convertRequestAssetUrlToImage();
        } catch (\Exception $e) {
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData(['error' => $e->getMessage(), 'errorcode' => $e->getCode()]);
        }

        return parent::execute();
    }
}
