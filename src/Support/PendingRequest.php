<?php

namespace MacsiDigital\API\Support;

use Illuminate\Http\Client\PendingRequest as IlluminatePendingRequest;

class PendingRequest extends IlluminatePendingRequest
{
    public function withOAuth1(OAuth1 $oAuth)
    {
        $this->withOptions(['auth' => 'oAuth']);
        $this->beforeSending($oAuth);
        return $this;
    }
}