<?php

namespace MacsiDigital\API\Support;

use Illuminate\Support\Str;
use MacsiDigital\API\Dev\Api;
use Illuminate\Support\Collection;

class Builder
{
	protected $resource;
	protected $request;
	protected $queries = [];
	protected $orders = [];
	protected $limit = null;
	protected $offset = null;
	protected $data = null;

	public function __construct($resource)
	{
		$this->request = $resource->client->newRequest();
		$this->resource = $resource;		
	}

	protected function retreiveEndPoint($type="index")
	{
		return $this->resource->getEndPoint($type);
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
    		return $response->json()['data'];
    	} else if($response->getStatusCode() == 404) {
    		return null;
    	} else {

    	}
    }

    public function findOrFail($id) 
    {
    	$response = $this->request->get($this->retreiveEndPoint('show').$id, $this->combineQueries());
    	if($response->ok()){
    		return $response->json()['data'];
    	} else if($response->getStatusCode() == 404) {
    		return $response->throw();
    	} else {

    	}
    }

    public function all() 
    {
    	$response = $this->request->get($this->retreiveEndPoint('index'), $this->combineQueries());
    	if($response->ok()){
    		return $this->collect($response->json()['data']);
    	} else {

    	}
    }

    public function get()
    {
    	if($this->data != null){
    		return $this->data;
    	}
    	$response = $this->request->get($this->retreiveEndPoint(), $this->combineQueries());
    	if($response->ok()){
    		return $this->data = $this->collect($response->json()['data']);
    	} else {
    		dd($response);
    	}
    }

    public function post($attributes)
    {
    	if($this->data != null){
    		return $this->data;
    	}
    	$response = $this->request->post($this->retreiveEndPoint('create'), $attributes, $this->combineQueries());
    	if($response->ok()){
    		return $this->data = $this->collect($response->json()['data']);
    	} else {
    		dd($response);
    	}
    }

    public function patch($attributes)
    {
    	if($this->data != null){
    		return $this->data;
    	}
    	$response = $this->request->patch($this->retreiveEndPoint('update'), $attributes, $this->combineQueries());
    	if($response->ok()){
    		return $this->data = $this->collect($response->json()['data']);
    	} else {
    		dd($response);
    	}
    }

    public function put($attributes)
    {
    	if($this->data != null){
    		return $this->data;
    	}
    	$response = $this->request->put($this->retreiveEndPoint('update'), $attributes, $this->combineQueries());
    	if($response->ok()){
    		return $this->data = $this->collect($response->json()['data']);
    	} else {
    		dd($response);
    	}
    }

    public function delete($id) 
    {
    	$response = $this->request->get($this->retreiveEndPoint('delete').$id, $this->combineQueries());
    	if($response->ok()){
    		return true;
    	} else {
    		
    	}
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

    /**
     * Get the number of models to return per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
     *
     * @param  int  $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function combineQueries() 
    {
    	return array_merge($this->queries, $this->orders);
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
    
    protected function collect($data)
    {
		$collection = new Collection;
    	foreach($data as $record){
    		$collection->push($this->hydrate($record));
    	}
    	return $collection;
    }

	protected function hydrate($array)
    {
    	return $this->resource->fresh()->fill($array);
    }

}