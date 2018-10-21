<?php

namespace Cloudinary\Cloudinary\Core;

class Image implements ImageInterface
{
    private $imagePath;

    private $relativePath;

    private $pathInfo;

    private function __construct($imagePath, $relativePath = '')
    {
        $this->imagePath = $imagePath;
        $this->relativePath = $relativePath;
        $this->pathInfo = pathinfo($this->imagePath);
    }

    public static function fromPath($imagePath, $relativePath = '')
    {
        return new Image($imagePath, $relativePath);
    }

    public function __toString()
    {
        return $this->imagePath;
    }

    public function getRelativePath()
    {
        return $this->relativePath;
    }

    public function getRelativeFolder()
    {
        $result = dirname($this->getRelativePath());
        return $result == '.' ? '' : $result;
    }

    public function getId()
    {
        return sprintf(
            '%s%s',
            $this->relativePath ? ($this->getRelativeFolder() . DIRECTORY_SEPARATOR) : '',
            $this->pathInfo['basename']
        );
    }

    public function getIdWithoutExtension()
    {
        return sprintf(
            '%s%s',
            $this->relativePath ? ($this->getRelativeFolder() . DIRECTORY_SEPARATOR) : '',
            $this->pathInfo['filename']
        );
    }

    public function getExtension()
    {
        return $this->pathInfo['extension'];
    }
}
