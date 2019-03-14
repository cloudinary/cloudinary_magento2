<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class CarouselStyle implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => 'None',
            ],
            [
                'value' => 'thumbnails',
                'label' => 'Thumbnails',
            ],
            [
                'value' => 'indicators',
                'label' => 'Indicators',
            ],
        ];
    }
}
