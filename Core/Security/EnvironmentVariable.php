<?php

namespace Cloudinary\Cloudinary\Core\Security;

interface EnvironmentVariable
{
    public function getCloud();
    public function getCredentials();
}
