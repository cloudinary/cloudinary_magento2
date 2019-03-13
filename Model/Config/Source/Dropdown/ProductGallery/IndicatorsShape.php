<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class IndicatorsShape implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
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
