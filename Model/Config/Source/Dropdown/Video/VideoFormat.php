<?php
namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video;
use Magento\Framework\Data\OptionSourceInterface;


class VideoFormat implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => 'None',
            ],
            [
                'value' => 'f_auto',
                'label' => 'Auto',
            ],
        ];
    }
}
