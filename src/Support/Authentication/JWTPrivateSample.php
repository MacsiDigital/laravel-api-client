<?php

namespace API\Support\Authentication;

use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use API\Contracts\Authentication;

class JWTPrivateSample implements Authentication
{
    // This will very much differ between API's so it should be created in each API. THis is just an example on how to do it
    public function returnClient($base_uri)
    {
        $options = [
            'base_uri' => $basde_uri,
            'headers' => [
                'Authorization' => 'Bearer '.$this->generateJWT(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
        $this->client = new Client($options);

        return $this;
    }

    public function generateJWT()
    {
        $token = [
            'iss' => config('zoom.api_key'),
            // The benefit of JWT is expiry tokens, we'll set this one to expire in 1 minute
            'exp' => time() + 60,
        ];

        return JWT::encode($token, config('zoom.api_secret'));
    }
}