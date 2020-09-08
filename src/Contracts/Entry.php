<?php

namespace MacsiDigital\API\Contracts;

interface Entry
{
    public function getNode($key);

    public function getRequest() ;

    public function setRequest($request);

    public function newRequest();
}
