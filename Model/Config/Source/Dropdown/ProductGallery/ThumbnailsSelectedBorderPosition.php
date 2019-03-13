<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class ThumbnailsSelectedBorderPosition implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'top',
                'label' => 'Top',
            ],
            [
                'value' => 'bottom',
                'label' => 'Bottom',
            ],
            [
                'value' => 'left',
                'label' => 'Left',
            ],
            [
                'value' => 'right',
                'label' => 'Right',
            ],
            [
                'value' => 'top-bottom',
                'label' => 'Top-Bottom',
            ],
            [
                'value' => 'left-right',
                'label' => 'Left-Right',
            ],
            [
                'value' => 'all',
                'label' => 'All',
            ],
        ];
    }
}
