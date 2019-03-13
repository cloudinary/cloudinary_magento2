<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class ThumbnailsSelectedStyle implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'border',
                'label' => 'Border',
            ],
            [
                'value' => 'gradient',
                'label' => 'Gradient',
            ],
            [
                'value' => 'all',
                'label' => 'All',
            ],
        ];
    }
}
