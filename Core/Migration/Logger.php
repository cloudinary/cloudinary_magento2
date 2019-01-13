<?php

namespace Cloudinary\Cloudinary\Core\Migration;

interface Logger
{
    public function warning($message, array $context = []);

    public function notice($message, array $context = []);

    public function error($message, array $context = []);

    public function debugLog($message);
}
