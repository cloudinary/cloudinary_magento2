<?php

namespace Cloudinary\Cloudinary\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

class CustomMediaPaths extends Value
{
    /**
     * Paths already handled by existing Cloudinary plugins
     */
    private const RESERVED_PATHS = [
        'catalog/product',
        'catalog/category',
        'wysiwyg',
    ];

    /**
     * @return CustomMediaPaths
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = (string) $this->getValue();
        if ($value === '') {
            return parent::beforeSave();
        }

        $lines = explode("\n", $value);
        $cleaned = [];

        foreach ($lines as $line) {
            $path = trim($line);
            $path = trim($path, '/');

            if ($path === '') {
                continue;
            }

            if (strpos($path, '..') !== false) {
                throw new ValidatorException(
                    __('Invalid path "%1": path traversal ("..") is not allowed.', $path)
                );
            }

            if (!preg_match('#^[a-zA-Z0-9_\-/]+$#', $path)) {
                throw new ValidatorException(
                    __('Invalid path "%1": only alphanumeric characters, hyphens, underscores, and forward slashes are allowed.', $path)
                );
            }

            foreach (self::RESERVED_PATHS as $reserved) {
                if (strpos($path, $reserved) === 0) {
                    throw new ValidatorException(
                        __('Path "%1" is already handled by Cloudinary and cannot be added as a custom path.', $path)
                    );
                }
            }

            $cleaned[] = $path;
        }

        $this->setValue(implode("\n", $cleaned));

        return parent::beforeSave();
    }
}
