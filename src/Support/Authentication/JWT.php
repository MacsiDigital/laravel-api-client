<?php

namespace MacsiDigital\API\Support\Authentication;

use Firebase\JWT\Key;
use Firebase\JWT\JWT as FirebaseJWT;

class JWT
{
    public static function generateToken($token, $secret)
    {
        return FirebaseJWT::encode($token, $secret, 'HS256');
    }

    public static function decodeToken($jwt, $secret)
    {
        return FirebaseJWT::decode($jwt, new Key($secret, 'HS256'));
    }
}
