<?php

namespace MacsiDigital\API\Support\Authentication;

use GuzzleHttp\Subscriber\Oauth\Oauth1 as GuzzleOauth1;

class OAuth1
{
    public static function generate($options)
    {
        return new GuzzleOauth1($options);
    }
}
