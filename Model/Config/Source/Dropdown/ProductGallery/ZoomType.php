<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class ZoomType implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'inline',
                'label' => 'Inline',
            ],
            [
                'value' => 'flyout',
                'label' => 'Flyout',
            ],
            [
                'value' => 'lightbox',
                'label' => 'Lightbox',
            ],
        ];
    }
}
