<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class AspectRatio implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'square',
                'label' => 'Square',
            ],
            [
                'value' => '1:1',
                'label' => '1:1',
            ],
            [
                'value' => '3:4',
                'label' => '3:4',
            ],
            [
                'value' => '4:3',
                'label' => '4:3',
            ],
            [
                'value' => '4:6',
                'label' => '4:6',
            ],
            [
                'value' => '6:4',
                'label' => '6:4',
            ],
            [
                'value' => '5:7',
                'label' => '5:7',
            ],
            [
                'value' => '7:5',
                'label' => '7:5',
            ],
            [
                'value' => '5:8',
                'label' => '5:8',
            ],
            [
                'value' => '8:5',
                'label' => '8:5',
            ],
            [
                'value' => '9:16',
                'label' => '9:16',
            ],
            [
                'value' => '16:9',
                'label' => '16:9',
            ],
        ];
    }
}
