<?php
/**
 * This class is not being used at the moment on Cloudinary_Cloudinary,
 * it has been replaced by Cloudinary\Cloudinary\Controller\Adminhtml\Ajax\RetrieveImage.
 */

namespace Cloudinary\Cloudinary\Controller\Adminhtml\Design\Config\FileUploader;

use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Theme\Model\Design\Config\FileUploader\FileProcessor;

/**
 * File Uploads Action Controller
 *
 * @api
 * @since 100.1.0
 */
class Save extends \Magento\Theme\Controller\Adminhtml\Design\Config\FileUploader\Save
{
    /**
     * @var MediaLibraryHelper
     */
    protected $mediaLibraryHelper;

    /**
     * @param Context $context
     * @param FileProcessor $fileProcessor
     * @param MediaLibraryHelper $mediaLibraryHelper
     */
    public function __construct(
        Context $context,
        FileProcessor $fileProcessor,
        MediaLibraryHelper $mediaLibraryHelper
    ) {
        parent::__construct($context, $fileProcessor);
        $this->mediaLibraryHelper = $mediaLibraryHelper;
    }

    /**
     * @inheritDoc
     * @since 100.1.0
     */
    public function execute()
    {
        try {
            $tmpfile = $this->mediaLibraryHelper->convertRequestAssetUrlToImage();
        } catch (\Exception $e) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(['error' => $e->getMessage(), 'errorcode' => $e->getCode()]);
        }

        return parent::execute();
    }
}
