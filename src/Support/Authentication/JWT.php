<?php

namespace MacsiDigital\API\Support\Authentication;

use Firebase\JWT\JWT as FirebaseJWT;

class JWT
{
    public static function generateToken($token, $secret)
    {
        return FirebaseJWT::encode($token, $secret);
    }

    public static function decodeToken($jwt, $secret)
    {
        return FirebaseJWT::decode($jwt, $secret, ['HS256']);
    }
}
