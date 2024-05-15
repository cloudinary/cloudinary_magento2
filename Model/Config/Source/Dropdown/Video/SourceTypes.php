<?php
namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video;
use Magento\Framework\Data\OptionSourceInterface;


class SourceTypes implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'webm/vp9',
                'label' => 'WebM/VP9',
            ],
            [
                'value' => 'mp4/h265',
                'label' => 'MP4/H.265',
            ],
            [
                'value' => 'mp4/h264',
                'label' => 'MP4/H.264',
            ]
        ];
    }
}
