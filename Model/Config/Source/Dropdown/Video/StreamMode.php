<?php
namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Autoplay
 * @author Ariel Ashri <arieliens@gmail.com>
 * Date: 14/01/2024
 * Time: 14:22
 */
class StreamMode implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => 'None',
            ],
            [
                'value' => 'abr',
                'label' => 'Adaptive bitrate streaming (beta)',
            ],
            [
                'value' => 'optimization',
                'label' => 'Video optimization',
            ],
        ];
    }
}
