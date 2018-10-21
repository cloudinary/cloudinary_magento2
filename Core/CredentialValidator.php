<?php

namespace Cloudinary\Cloudinary\Core;

class CredentialValidator
{
    public function validate(Credentials $credentials)
    {
        $signedValidationUrl = (string)Security\SignedConsoleUrl::fromConsoleUrlAndCredentials(
            Security\ConsoleUrl::fromPath("media_library/cms"),
            $credentials
        );

        $request = new ValidateRemoteUrlRequest($signedValidationUrl);
        return $request->validate();

    }
}
