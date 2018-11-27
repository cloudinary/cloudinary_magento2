<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown;

use Magento\Framework\Data\OptionSourceInterface;

class Quality implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => 'Magento\'s Default',
            ],
            [
                'value' => '20',
                'label' => '20%',
            ],
            [
                'value' => '30',
                'label' => '30%',
            ],
            [
                'value' => '40',
                'label' => '40%',
            ],
            [
                'value' => '50',
                'label' => '50%',
            ],
            [
                'value' => '60',
                'label' => '60%',
            ],
            [
                'value' => '70',
                'label' => '70%',
            ],
            [
                'value' => '80',
                'label' => '80%',
            ],
            [
                'value' => '90',
                'label' => '90%',
            ],
            [
                'value' => '100',
                'label' => '100%',
            ],
            [
                'value' => 'auto',
                'label' => 'Auto',
            ]
        ];
    }
}
