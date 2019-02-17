<?php
/**
 * This class is not being used at the moment on Cloudinary_Cloudinary,
 * it has been replaced by Cloudinary\Cloudinary\Controller\Adminhtml\Ajax\RetrieveImage.
 */

namespace Cloudinary\Cloudinary\Controller\Adminhtml\Product\Gallery;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class Upload extends \Magento\Catalog\Controller\Adminhtml\Product\Gallery\Upload implements HttpPostActionInterface
{
    /**
     * @var MediaLibraryHelper
     */
    protected $mediaLibraryHelper;

    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param MediaLibraryHelper $mediaLibraryHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        MediaLibraryHelper $mediaLibraryHelper
    ) {
        parent::__construct($context, $resultRawFactory);
        $this->mediaLibraryHelper = $mediaLibraryHelper;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        try {
            $tmpfile = $this->mediaLibraryHelper->convertRequestAssetUrlToImage();
        } catch (\Exception $e) {
            $response = $this->resultRawFactory->create();
            $response->setHeader('Content-type', 'text/plain');
            $response->setContents(json_encode(['error' => $e->getMessage(), 'errorcode' => $e->getCode()]));
            return $response;
        }

        return parent::execute();
    }
}
