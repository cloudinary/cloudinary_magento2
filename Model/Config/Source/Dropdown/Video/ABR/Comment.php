<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video\ABR;

use Magento\Framework\UrlInterface;
use Magento\Config\Model\Config\CommentInterface;


class Comment implements CommentInterface
{
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        $url = 'https://cloudinary.com/documentation/adaptive_bitrate_streaming';

        $comment = 'Adaptive bitrate streaming (Beta), Dynamic adaptive streaming over HTTP (MPEG-DASH),
        HTTP live streaming (HLS).
        Adaptive bitrate streaming is a video delivery technique that adjusts the quality of a video stream in real time according to detected bandwidth and CPU capacity.';
        $comment .= ' Read more about';
        $comment .= '<br><a href="'.$url.'" target="_blank">adaptive bitrate streaming</a>';

        return $comment;
    }
}
