<?php

namespace Cloudinary\Cloudinary\Plugin;

use Magento\Framework\View\Asset\Minification;

class ExcludeFilesFromMinification
{
    public function afterGetExcludes(Minification $subject, array $result, $contentType)
    {
        if ($contentType == 'js') {
            $result[] = '//media-library.cloudinary.com/global/all';
            $result[] = '//product-gallery.cloudinary.com/all';
        }
        return $result;
    }
}
