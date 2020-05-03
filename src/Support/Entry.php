<?php

namespace MacsiDigital\API\Support;

use BadMethodCallException;
use Illuminate\Support\Str;
use MacsiDigital\API\Facades\Client;
use MacsiDigital\API\Facades\ClientContract;
use MacsiDigital\API\Exceptions\NodeNotFoundException;
use MacsiDigital\API\Contracts\Entry as EntryContract;

class Entry implements EntryContract
{
    protected $request = null;
    protected $modelNamespace = '';

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if(method_exists($this, $method)){
            return $this->$method(...$parameters);
        } else {
            try {
                return $this->$method;
            } catch(NodeNotFoundException $e){
                throw new BadMethodCallException(sprintf(
                    'Call to undefined method %s::%s()', static::class, $method
                ));
            }
        }
    }

    public function __get($key)
    {
        return $this->getNode($key);
    }

    public function getNode($key)
    {
        $class = $this->modelNamespace.Str::studly($key);
        if (class_exists($class)) {
            return new $class($this);
        }
        throw new NodeNotFoundException('No node with name '.$key);
    }

    public function getRequest()
    {
        if(!$this->hasRequest()){
            $this->setReqeust($this->newRequest());
        }
        return $this->request;
    }

    public function hasRequest() 
    {
        return $this->request != null;
    }

    public function setRequest($request) 
    {
        $this->request = $request;
        return $this;
    }

    public function newRequest()
    {
        $this->setRequest(new Client);
    }

}
