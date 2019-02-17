<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Cloudinary\Cloudinary\Model\Observer;

use Cloudinary\Cloudinary\Model\Configuration;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductGalleryChangeTemplate implements ObserverInterface
{

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(
        Configuration $configuration
    ) {
        $this->configuration = $configuration;
    }

    /**
     * @param mixed $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->configuration->isEnabled()) {
            return $this;
        }
        $observer->getBlock()->setTemplate('Cloudinary_Cloudinary::catalog/product/helper/gallery.phtml');
    }
}
