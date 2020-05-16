<?php
namespace MacsiDigital\API\Support;

use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use MacsiDigital\API\Traits\ForwardsCalls;

class ResultSet implements Arrayable, ArrayAccess, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
	use ForwardsCalls;

	protected $apiTotalRecords;
	protected $apiPerPage;
	protected $apiCurrentPage;
	protected $apiLastPage;

	protected $totalRecords;
	protected $perPage;
	protected $currentPage;
	protected $lastPage;

	protected $hasOwnMeta = false;	

	protected $items;
	protected $downloaded = 0;

	protected $raw = false;
	protected $all = false;

	protected $queries = 0;
	protected $maxQueries = 10;

	protected $builder;
	protected $responses = [];
	protected $resource;

	public function __construct($builder, $response, $resource, $all=false)
	{		
		$this->all = $all;
		$this->builder = $builder;
		$this->resource = $resource;
		$this->setMaxQueries($resource->client->getMaxQueries());
		$this->setRawStatus($builder->isRaw());
		if(!$this->raw){
			$this->items = new Collection;
		}
		$this->builder->raw();
		$this->processResponse($response);
		$this->processRecordSweep();
	}

	public function setMaxQueries($amount)
	{
		$this->maxQueries = $amount;
	}

	protected function setRawStatus(bool $status=false)
	{
		$this->raw = $status;
	}

	protected function processResponse($response) 
	{
		$this->addReponse($response);
		$this->processMeta($response->json());
		$this->populate($response->json());
		$this->incrementQueries();
	}

	protected function incrementQueries() 
	{
		$this->queries++;
	}

	protected function canQuery()
	{
		if($this->maxQueries == 0 || $this->maxQueries == ''){
			return true;
		}
		return $this->maxQueries > $this->queries;
	}

	protected function incrementTotalDownloads($i=1) 
	{
		$this->downloaded += $i;
	}

	public function resetQueryCount() 
	{
		$this->queries = 0;
	}

	protected function addReponse($response) 
	{
		$this->responses[] = $response;
	}

	protected function populate($array) 
	{
		if($this->raw){
			$this->items = array_merge($this->items, $array[$this->resource->getApiDataField()]);
			$this->incrementTotalDownloads(count($array[$this->resource->getApiDataField()]));
			return $this;
		} else{
			foreach($array[$this->resource->getApiDataField()] as $object){
				$this->items->push($this->resource->fresh()->fill($object));
				$this->incrementTotalDownloads();
			}
		}
	}

	protected function processMeta($array) 
	{
		if(Arr::has($array, $this->resource->client->getResultsPageField())){
			$this->apiCurrentPage = (int) Arr::get($array, $this->resource->client->getResultsPageField());
		}
		if(Arr::has($array, $this->resource->client->getResultsTotalPagesField())){
			$this->apiLastPage = (int) Arr::get($array, $this->resource->client->getResultsTotalPagesField());
		}
		if(Arr::has($array, $this->resource->client->getResultsPageSizeField())){
			$this->apiPerPage = (int) Arr::get($array, $this->resource->client->getResultsPageSizeField());
		}
		if(Arr::has($array, $this->resource->client->getResultsTotalRecordsField())){
			$this->apiTotalRecords = (int) Arr::get($array, $this->resource->client->getResultsTotalRecordsField());
		}
	}

	protected function apiHasMorePages() 
	{
		return $this->apiLastPage() > $this->apiCurrentPage();
	}

	protected function apiHasPerPage() 
	{
		return $this->apiPerPage != '';
	}

	protected function apiIsFirstPage() 
	{
		return $this->apiCurrentPage() <= 1;
	}

	protected function apiLastPage() 
	{
		return $this->apiLastPage;
	}

	protected function apiFirstPage() 
	{
		return 1;
	}

	protected function apiPerPage() 
	{
		return $this->apiPerPage;
	}

	protected function apiCurrentPage() 
	{
		return $this->apiCurrentPage;
	}

	protected function apiNextPageNumber() 
	{
		return ++$this->apiCurrentPage;
	}

	protected function apiPreviousPageNumber() 
	{
		return --$this->apiCurrentPage;
	}

	public function hasMorePages() 
	{
		if($this->hasOwnMeta){
			return $this->lastPage() > $this->currentPage();
		} else {
			return $this->apiLastPage();
		}
	}

	public function hasPerPage() 
	{
		if($this->hasOwnMeta){
			return true;
		} else {
			return $this->apiHasPerPage();
		}
	}

	public function isFirstPage() 
	{
		if($this->hasOwnMeta){
			return $this->currentPage() <= 1;
		} else {
			return $this->isFirstPage();
		}
	}

	public function lastPage() 
	{
		if($this->hasOwnMeta){
			return $this->lastPage;
		} else {
			return $this->apiLastPage();
		}
	}

	public function firstPage() 
	{
		if($this->hasOwnMeta){
			return 1;
		} else {
			return $this->apiFirstPage();
		}
	}

	public function perPage() 
	{
		if($this->hasOwnMeta){
			return $this->perPage;
		} else {
			return $this->apiPerPage();
		}
	}

	public function currentPage() 
	{
		if($this->hasOwnMeta){
			return $this->currentPage;
		} else {
			return $this->apiCurrentPage();
		}
	}

	public function nextPageNumber() 
	{
		if($this->hasOwnMeta){
			return ++$this->currentPage;
		} else {
			return $this->apiNextPageNumber();
		}
	}

	public function previousPageNumber() 
	{
		if($this->hasOwnMeta){
			return --$this->currentPage;
		} else {
			return $this->apiPreviousPageNumber();
		}
	}

	public function processRecordSweep() 
	{
		if($this->all || !$this->builder->shouldPaginate()){
			$this->recursiveRecordCollection();
			$this->updateMeta();
			$this->resetQueryCount();
		}
	}

	public function recursiveRecordCollection()
	{
		if($this->apiHasMorePages() && $this->canQuery()){
			if($this->apiHasPerPage()){
				$this->builder->setPerPage($this->apiPerPage());	
			}
			$this->builder->setPage($this->apiNextPageNumber());
			$response = $this->builder->get();
			$this->processResponse($response);
			$this->recursiveRecordCollection();
		}
	}

	public function updateMeta() 
	{
		$this->hasOwnMeta = true;
		$this->perPage = $this->downloaded;
		if($this->apiTotalRecords != ''){
			$this->totalRecords = $this->apiTotalRecords;
			$this->lastPage = (int) ceil($this->totalRecords / $this->downloaded);
			$this->currentPage = (int) floor($this->apiCurrentPage() / ($this->downloaded / $this->apiPerPage()));
		}
	}

    public function isEmpty()
    {
        return $this->items->isEmpty();
    }

    public function count()
    {
        return $this->items->count();
    }

    public function isNotEmpty()
    {
        return $this->items->isNotEmpty();
    }

    public function getCollection()
    {
        return $this->items;
    }

	public function getResults() 
	{
		return $this->getCollection();
	}

    public function getIterator()
    {
        return $this->items->getIterator();
    }

    public function offsetExists($key)
    {
        return $this->items->has($key);
    }

    public function offsetGet($key)
    {
        return $this->items->get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->items->put($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->items->forget($key);
    }

    public function toArray()
    {
        return [
            'current_page' => $this->currentPage(),
            'data' => $this->items->toArray(),
            'last_page' => $this->lastPage(),
            'per_page' => $this->perPage(),
            'total' => $this->totalRecords(),
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->getCollection(), $method, $parameters);
    }

}