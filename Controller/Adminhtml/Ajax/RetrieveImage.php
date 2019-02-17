<?php

namespace Cloudinary\Cloudinary\Controller\Adminhtml\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RetrieveImage extends \Magento\ProductVideo\Controller\Adminhtml\Product\Gallery\RetrieveImage implements HttpPostActionInterface
{
    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        return parent::execute();
    }
}
