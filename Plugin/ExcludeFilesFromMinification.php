<?php

namespace Cloudinary\Cloudinary\Plugin;

use Magento\Framework\View\Asset\Minification;

class ExcludeFilesFromMinification
{
    public function aroundGetExcludes(Minification $subject, callable $proceed, $contentType = null)
    {
        $result = $proceed($contentType);
        if (!$contentType || $contentType === 'js') {
            $result[] = '//media-library.cloudinary.com/global/all';
            $result[] = '//product-gallery.cloudinary.com/all';
        }
        return $result;
    }
}
