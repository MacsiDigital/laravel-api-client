<?php

namespace MacsiDigital\API\Support;

use Illuminate\Support\Str;
use MacsiDigital\API\Dev\Api;
use Illuminate\Support\Collection;
use MacsiDigital\API\Support\ResultSet;

class Builder
{
	protected $resource;
	protected $request;
	protected $queries = [];
	protected $orders = [];
	protected $limit = null;
	protected $offset = null;
	protected $data = null;
    protected $raw = false;
    protected $paginate = false;

    protected $pageField;
    protected $perPageField;

    protected $perPage = '';
    protected $page = 1;

    protected $maxPerPage = '';
    protected $minPerPage = '';


	public function __construct($resource)
	{
		$this->request = $resource->client->newRequest();
		$this->resource = $resource;
        $this->prepare($resource);
	}

    protected function prepare($resource)
    {
        $this->setPerPageField($resource->client->getPerPageField());
        $this->setPageField($resource->client->getPageField());
        $this->raw($resource->client->getRaw());
        $this->paginate($resource->client->getPagination());
        if($this->shouldPaginate()){
            $this->setPerPage($resource->client->getDefaultPaginationRecords());
        }
        $this->setMaxPerPage($resource->client->getMaxPaginationRecords());
        $this->setMinPerPage($resource->client->getMinPaginationRecords());
    }

    protected function setPerPageField($field)
    {
        $this->perPageField = $field;
        return $this;
    }

    protected function setMaxPerPage($amount)
    {
        $this->maxPerPage = $amount;
        return $this;
    }

    protected function setMinPerPage($amount)
    {
        $this->minPerPage = $amount;
        return $this;
    }

    protected function setPageField($field)
    {
        $this->pageField = $field;
        return $this;
    }

	protected function retreiveEndPoint($type="index")
	{
		return $this->resource->getEndPoint($type);
	}

    protected function getApiDataField()
    {
        return $this->resource->getApiDataField();
    }

    public function find($id, $column="") 
    {
    	if(is_array($id)){
    		if($column == ''){
	    		$column = $this->resource->getKeyName().'s';
	    	}
    		return $this->whereIn($id, $column);
    	}
    	$response = $this->request->get($this->retreiveEndPoint('show').$id, $this->combineQueries());
    	if($response->ok()){
            if($this->raw){
                return $response;
            }
    		return $this->hydrate($response);
    	} else if($response->getStatusCode() == 404) {
            if($this->raw){
                return $response;
            }
    		return null;
    	} else {
            return $response;
    	}
    }

    public function findOrFail($id) 
    {
    	$response = $this->request->get($this->retreiveEndPoint('show').$id, $this->combineQueries());
    	if($response->ok()){
            if($this->raw){
                return $response;
            }
    		return $this->hydrate($response);
    	} else if($response->getStatusCode() == 404) {
            if($this->raw){
                return $response;
            }
    		return $response->throw();
    	} else {
            return $response;
    	}
    }

    public function all() 
    {
    	$response = $this->request->get($this->retreiveEndPoint('index'), $this->addPagination($this->combineQueries()));
    	if($response->ok()){
            if($this->raw){
                return $response;
            }
    		return $this->processAllResultSet($response);
    	} else {
            return $response;
    	}
    }

    public function get()
    {
    	if($this->data != null){
    		return $this->data;
    	}
    	$response = $this->request->get($this->retreiveEndPoint(), $this->addPagination($this->combineQueries()));
    	if($response->ok()){
            if($this->raw){
                return $response;
            }
    		return $this->data = $this->processResultSet($response);
    	} else {
    		return $response;
    	}
    }

    public function post($attributes)
    {
    	$response = $this->request->post($this->retreiveEndPoint('create'), $attributes, $this->combineQueries());
    	if($response->successful()){
            if($this->raw){
                return $response;
            }
    		return $this->hydrate($response, false);
    	} else {
            if($this->raw){
                return $response;
            }
            return $this->update(['status_code' => $response->status(), 'response' => $response]);
    	}
    }

    public function patch($attributes)
    {
    	$response = $this->request->patch($this->retreiveEndPoint('update'), $attributes, $this->combineQueries());
    	if($response->successful()){
            if($this->raw){
                return $response;
            }
    		return $this->hydrate($response, false);
    	} else {
            if($this->raw){
                return $response;
            }
            return $this->update(['status_code' => $response->status(), 'response' => $response]);
    	}
    }

    public function put($attributes)
    {
    	$response = $this->request->put($this->retreiveEndPoint('update'), $attributes, $this->combineQueries());
    	if($response->successful()){
            if($this->raw){
                return $response;
            }
    		return $this->hydrate($response, false);
    	} else {
            if($this->raw){
                return $response;
            }
    		return $this->update(['status_code' => $response->status(), 'response' => $response]);
    	}
    }

    public function delete() 
    {
    	return $this->request->delete($this->retreiveEndPoint('delete'), $this->combineQueries());
    }

    public function first()
    {
    	return $this->get()->first();
    }

    public function firstWhere($column, $value)
    {
    	$this->where($column, $value);
    	return $this->first();
    }
    
    public function where($column, $value)
    {
    	if($this->data != null){
    		$this->data = null;
    	}
        $this->queries[$column] = $value;

        return $this;
    }

    public function whereIn(array $values, $column="") 
    {
    	$string = implode(',', $values);
    	return $this->where($column, $string)->get();
    }

    public function orderBy($value, $column='order') 
    {
    	$this->orders[$column] = $value;
    	return $this;
    }

    public function count() 
    {
    	if(isset($this->data)){
    		return $this->data->count();
    	} else {
    		$this->data = $this->get();
    	}
    	return $this->data->count();
    }

    public function paginate($status=true) 
    {
        $this->paginate = $status;
        return $this;
    }

    public function shouldPaginate() 
    {
        return $this->paginate;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getPerPage()
    {
        return $this->perPage;
    }

    public function setPerPage($perPage)
    {

        $this->perPage = $perPage;

        return $this;
    }

    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    public function raw($status=true) 
    {
        $this->raw = $status;
        return $this;
    }

    public function isRaw()
    {
        return $this->raw;
    }

    public function combineQueries() 
    {
        return array_merge($this->queries, $this->orders);
    }

    public function addPagination($array) 
    {
        if($this->perPage != ''){
            $array[$this->perPageField] = $this->perPage;
        }
        if($this->page != ''){
            $array[$this->pageField] = $this->page;
        }
        return $array;
    }

    public function reset()
    {
    	$this->resetQueries();
    	$this->resetOrders();
    	$this->resetData();
    	$this->resetLimit();
    	$this->resetOffset();
    	return $this;
    }

    public function resetData()
    {
    	$this->data = null;
    	return $this;
    }

    public function resetLimit()
    {
    	$this->limit = null;
    	return $this;
    }

    public function resetOffset()
    {
    	$this->offset = null;
    	return $this;
    }

    public function resetQueries()
    {
    	$this->queries = [];
    	$this->resetData();
    	return $this;
    }

    public function resetOrders()
    {
    	$this->orders = [];
    	return $this;
    }
    
    protected function processResultSet($response)
    {
    	return new ResultSet($this, $response, $this->resource);
    }

    protected function processAllResultSet($response)
    {
        return new ResultSet($this, $response, $this->resource, true);
    }

    protected function hydrate($response, $fresh=true)
    {
        if($fresh){
            return $this->create($response->json()[$this->getApiDataField()]);
        } else {
            return $this->update($response->json()[$this->getApiDataField()]);
        }
    }

	protected function create($array)
    {        
    	return $this->resource->fresh()->fill($array);
    }

    protected function update($array)
    {
        return $this->resource->fill($array);
    }

}