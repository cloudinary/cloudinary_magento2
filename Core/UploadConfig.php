<?php
/**
 * Created by PhpStorm.
 * User: danielk
 * Date: 19/01/16
 * Time: 14:30
 */

namespace Cloudinary\Cloudinary\Core;


class UploadConfig
{
    /**
     * @var boolean
     */
    private $useFilename;

    /**
     * @var boolean
     */
    private $uniqueFilename;

    /**
     * @var boolean
     */
    private $overwrite;

    private function __construct($useFilename, $uniqueFilename, $overwrite)
    {
        $this->useFilename = $useFilename;
        $this->uniqueFilename = $uniqueFilename;
        $this->overwrite = $overwrite;
    }

    public static function fromBooleanValues($useFilename, $uniqueFilename, $overwrite)
    {
        return new UploadConfig($useFilename, $uniqueFilename, $overwrite);
    }

    /**
     * @return boolean
     */
    public function useFilename()
    {
        return $this->useFilename;
    }

    /**
     * @return boolean
     */
    public function uniqueFilename()
    {
        return $this->uniqueFilename;
    }

    /**
     * @return boolean
     */
    public function overwrite()
    {
        return $this->overwrite;
    }

    public function toArray()
    {
        return [
            "use_filename" => $this->useFilename,
            "unique_filename" => $this->uniqueFilename,
            "overwrite" => $this->overwrite,
        ] ;
    }
}
