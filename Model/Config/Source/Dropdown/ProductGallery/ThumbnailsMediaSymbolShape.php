<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class ThumbnailsMediaSymbolShape implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => 'None',
            ],
            [
                'value' => 'round',
                'label' => 'Round',
            ],
            [
                'value' => 'square',
                'label' => 'Square',
            ],
            [
                'value' => 'radius',
                'label' => 'Radius',
            ],
        ];
    }
}
