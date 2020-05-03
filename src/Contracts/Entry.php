<?php

namespace MacsiDigital\API\Contracts;

use MacsiDigital\API\Facades\Client as ClientFacade;

interface Entry
{

    public function getNode($key);

    public function getRequest() ;

    public function setRequest(Client $request);

    public function newRequest();

}
