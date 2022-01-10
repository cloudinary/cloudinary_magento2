<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cloudinary\Cloudinary\Model\Framework\File;

/**
 * File upload class
 *
 * ATTENTION! This class must be used like abstract class and must added
 * validation by protected file extension list to extended class
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @api
 * @since 100.0.2
 */
class Uploader extends \Magento\Framework\File\Uploader
{
    /**
     * Correct filename with special chars and spaces; also trim excessively long filenames
     *
     * @param string $fileName
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getCorrectFileName($fileName)
    {
        $fileName = preg_replace('/[^a-z0-9_\\-\\.]+/i', '_', $fileName);
        $fileInfo = pathinfo($fileName);
        $index = 1;
        while ($fileExists($fileInfo['dirname'] . '/' . $fileInfo['basename'])) {
            $fileInfo['basename'] = $fileInfo['filename'] . '_' . $index++ . '.' . $fileInfo['extension'];
        }
        // account for excessively long filenames that cannot be stored completely in database
        if (strlen($fileInfo['basename']) > 180) {
            throw new \InvalidArgumentException('Filename is too long; must be 180 characters or less');
        }

        if (preg_match('/^_+$/', $fileInfo['filename'])) {
            $fileName = 'file.' . $fileInfo['extension'];
        }

        return $fileName;
    }
}
