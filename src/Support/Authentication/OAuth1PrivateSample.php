<?php

namespace API\Support\Authentication;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use API\Contracts\Authentication;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class OAuth1PrivateSample implements Authentication
{
    // This will very much differ between API's so it should be created in each API. THis is just an example on how to do it
    public function returnClient($base_uri)
    {
        $middleware = new Oauth1([
            'consumer_key' => config('xero.oauth.consumer_key'),
            'token' => config('xero.oauth.consumer_key'),
            'private_key_file' => storage_path(config('xero.oauth.rsa_private_key')),
            'private_key_passphrase' => config('xero.oauth.rsa_private_key_passphrase'),
            'signature_method' => Oauth1::SIGNATURE_METHOD_RSA,
        ]);

        $stack = HandlerStack::create();
        $stack->push($middleware);

        $options = [
            'base_uri' => $base_uri,
            'handler' => $stack,
            'auth' => 'oauth',
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ],
        ];

        return new Client($options);
    }
}