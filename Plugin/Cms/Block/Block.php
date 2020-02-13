<?php

namespace Cloudinary\Cloudinary\Plugin\Cms\Block;

use Cloudinary\Cloudinary\Plugin\CmsBlockLazyloadAbstract;
use Magento\Cms\Block\Block as CmsBlockBlock;

/**
 * Class Blocks
 */
class Block extends CmsBlockLazyloadAbstract
{

    /**
     * @method afterToHtml
     * @param  CmsBlockBlock $subject
     * @param  string        $html
     * @return string
     */
    public function afterToHtml(CmsBlockBlock $subject, $html)
    {
        return $this->process($subject, $html);
    }
}
