<?php

namespace Cloudinary\Cloudinary\Core\Exception;

class FileExists extends MigrationError
{
    const DEFAULT_MESSAGE = 'File already exists (cloudinary is case insensitive!!).';
}
