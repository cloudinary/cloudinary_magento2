<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown;

use Magento\Framework\Data\OptionSourceInterface;

class FreeTransformBehavior implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'add',
                'label' => 'Add',
            ],
            [
                'value' => 'override',
                'label' => 'Override',
            ],
        ];
    }
}
