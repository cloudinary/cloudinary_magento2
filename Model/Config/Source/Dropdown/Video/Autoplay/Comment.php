<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video\Autoplay;

use Magento\Framework\UrlInterface;
use Magento\Config\Model\Config\CommentInterface;

/**
 * Class Comment
 * @package Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video\Autoplay
 * @author Ariel Ashri <arieliens@gmail.com>
 * Date: 07/03/2024
 * Time: 14:04
 */
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
        $url = 'https://cloudinary.com/glossary/video-autoplay';

        $comment = 'Please note that when choosing "always", the video will autoplay without sound (muted). This is a built-in browser feature and applies';
        $comment .= ' to all major browsers. ';
        $comment .= '<br><a href="'.$url.'" target="_blank">Read more about muted autoplay</a>';

        return $comment;
    }
}
