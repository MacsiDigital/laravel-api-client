<?php

namespace MacsiDigital\API\Support\Authentication;

use Firebase\JWT\JWT as FirebaseJWT;

class JWT
{
    protected $options;

    public static function token($token, $secret)
    {
        return FirebaseJWT::encode($token, $secret);

    }
}