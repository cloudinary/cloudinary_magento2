<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class ZoomViewerPosition implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'top',
                'label' => 'Top',
            ],
            [
                'value' => 'right',
                'label' => 'Right',
            ],
            [
                'value' => 'left',
                'label' => 'Left',
            ],
            [
                'value' => 'bottom',
                'label' => 'Bottom',
            ],
        ];
    }
}
