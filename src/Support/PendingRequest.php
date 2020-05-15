<?php

namespace MacsiDigital\API\Support;

class PendingRequest extends IlluminatePendingRequest
{
    public function withOAuth1(Oauth1 $oauth)
    {
        $this->withOptions(['auth' => 'oauth']);
        $this->beforeSending($Oauth);
    }
}