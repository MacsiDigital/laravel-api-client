<?php

namespace MacsiDigital\API\Traits;

trait InteractsWithAPI
{

    protected $allowedMethods = [];

    protected $indexEndPoint;
    protected $insertEndPoint;
    protected $showEndPoint;
    protected $updateEndPoint;
    protected $deleteEndPoint;

    public function canPerform($type) 
    {
    	return in_array($type, $this->getAllowedMethods());
    }

    public function cantPerform($type) 
    {
    	return ! $this->canPerform($type);
    }

    public function getAllowedMethods() 
    {
    	return $this->allowedMethods;
    }

    public function getIndexEndPoint() 
    {
        
    }

    public function getInsertEndPoint() 
    {
        
    }

    public function getShowEndPoint() 
    {
        
    }

    public function getUpdateEndPoint() 
    {
        
    }

    public function getDeleteEndPoint() 
    {
        
    }

}