<?php
namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video;
use Magento\Framework\Data\OptionSourceInterface;


class VideoQuality implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => 'Not set',
            ],
            [
                'value' => 'q_auto',
                'label' => 'Auto',
            ],
            [
                'value' => 'q_auto:best',
                'label' => 'Auto best',
            ],
            [
                'value' => 'q_auto:good',
                'label' => 'Auto good',
            ],
            [
                'value' => 'q_auto:eco',
                'label' => 'Auto eco',
            ],
            [
                'value' => 'q_auto:low',
                'label' => 'Auto low',
            ],
            [
                'value' => 'q_100',
                'label' => '100',
            ],
            [
                'value' => 'q_80',
                'label' => '80',
            ],
            [
                'value' => '60',
                'label' => '60',
            ],
            [
                'value' => 'q_40',
                'label' => '40',
            ],
            [
                'value' => 'q_20',
                'label' => '20',
            ]
        ];
    }
}
