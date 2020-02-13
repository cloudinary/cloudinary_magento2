<?php

namespace Cloudinary\Cloudinary\Plugin\Cms\Block\Widget;

use Cloudinary\Cloudinary\Plugin\CmsBlockLazyloadAbstract;
use Magento\Cms\Block\Widget\Block as CmsBlockWidget;

/**
 * Class Blocks
 */
class Block extends CmsBlockLazyloadAbstract
{

    /**
     * @method afterToHtml
     * @param  CmsBlockWidget $subject
     * @param  string         $html
     * @return string
     */
    public function afterToHtml(CmsBlockWidget $subject, $html)
    {
        return $this->process($subject, $html);
    }
}
