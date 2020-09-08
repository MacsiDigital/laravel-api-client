<?php
namespace MacsiDigital\API\Support;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use IteratorAggregate;
use JsonSerializable;
use MacsiDigital\API\Exceptions\OutOfResultSetException;
use MacsiDigital\API\Traits\ForwardsCalls;

class ResultSet implements Arrayable, ArrayAccess, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    use ForwardsCalls;

    protected $apiTotalRecords;
    protected $apiPerPage;
    protected $apiCurrentPage;
    protected $apiLastPage;
    protected $apiPagesDownloaded = [];

    protected $totalRecords;
    protected $perPage;
    protected $currentPage;
    protected $lastPage;
    protected $pagesDownloaded = [];

    protected $hasOwnMeta = false;

    protected $paginationMethod = 'fresh';

    protected $items = [];
    protected $downloaded = 0;

    protected $raw = false;
    protected $all = false;

    protected $queries = 0;
    protected $maxQueries = 10;

    protected $builder;
    protected $responses = [];
    protected $resource;

    public function __construct($builder, $response, $resource, $all = false)
    {
        $this->all = $all;
        $this->builder = $builder;
        $this->resource = $resource;
        $this->setMaxQueries($resource->client->getMaxQueries());
        $this->setRawStatus($builder->isRaw());
        if (! $this->raw) {
            $this->items = new Collection;
        }
        $this->builder->raw();
        $this->processResponse($response);
        if ($this->items->count() > 0) {
            $this->processRecordSweep();
        }
    }

    public function setMaxQueries($amount)
    {
        $this->maxQueries = $amount;
    }

    protected function setRawStatus(bool $status = false)
    {
        $this->raw = $status;
    }

    protected function processResponse($response)
    {
        $this->addResponse($response);
        $this->populate($response->json());
        $this->processMeta($response->json());
        $this->incrementQueries();
    }

    protected function incrementQueries()
    {
        $this->queries++;
    }

    protected function canQuery()
    {
        if ($this->maxQueries == 0 || $this->maxQueries == '') {
            return true;
        }

        return $this->maxQueries > $this->queries;
    }

    protected function incrementTotalDownloads($i = 1)
    {
        $this->downloaded += $i;
    }

    public function resetQueryCount()
    {
        $this->queries = 0;
    }

    protected function addResponse($response)
    {
        $this->responses[] = $response;
    }

    protected function populate($array)
    {
        if ($this->raw) {
            $this->items = array_merge($this->items, $array[$this->resource->getApiMultipleDataField()]);
            $this->incrementTotalDownloads(count($array[$this->resource->getApiMultipleDataField()]));

            return $this;
        } else {
            if (isset($array[$this->resource->getApiMultipleDataField()])) {
                foreach ($array[$this->resource->getApiMultipleDataField()] as $object) {
                    $this->items->push($this->resource->newFromBuilder($this->resource->passOnAttributes($object)));
                    $this->incrementTotalDownloads();
                }
            }
        }
    }

    protected function processMeta($array)
    {
        if (Arr::has($array, $this->resource->client->getResultsPageField())) {
            $this->apiCurrentPage = (int) Arr::get($array, $this->resource->client->getResultsPageField());
        } else {
            $this->apiCurrentPage = $this->builder->getPage();
        }
        $this->apiPagesDownloaded[] = $this->apiCurrentPage;

        if (Arr::has($array, $this->resource->client->getResultsPageSizeField())) {
            $this->apiPerPage = (int) Arr::get($array, $this->resource->client->getResultsPageSizeField());
        } else {
            $this->apiPerPage = $this->builder->getPerPage();
        }

        if (Arr::has($array, $this->resource->client->getResultsTotalRecordsField())) {
            $this->apiTotalRecords = (int) Arr::get($array, $this->resource->client->getResultsTotalRecordsField());
        } elseif ($this->downloaded < $this->apiPerPage) {
            $this->apiTotalRecords = $this->downloaded;
        }

        if (Arr::has($array, $this->resource->client->getResultsTotalPagesField())) {
            $this->apiLastPage = (int) Arr::get($array, $this->resource->client->getResultsTotalPagesField());
        } elseif ($this->downloaded < $this->apiPerPage) {
            $this->apiLastPage = 1;
        }
    }

    protected function pageDownloaded()
    {
        $this->pagesDownloaded[$this->currentPage()] = $this->items;
    }

    protected function pageHasBeenDownloaded($page)
    {
        return array_key_exists($page, $this->pagesDownloaded);
    }

    protected function getDownloadedPage($page)
    {
        return $this->pagesDownloaded[$page];
    }

    protected function apiPageDownloaded($page)
    {
        $this->apiPagesDownloaded[] = $page;
    }

    protected function apiPageHasBeenDownloaded($page)
    {
        return in_array($page, $this->apiPagesDownloaded);
    }

    protected function apiHasMorePages()
    {
        if ($this->apiLastPage() == null) {
            return true;
        }

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
        return $this->apiCurrentPage + 1;
    }

    protected function apiPreviousPageNumber()
    {
        return $this->apiCurrentPage - 1;
    }

    protected function apiTotalRecords()
    {
        return $this->apiTotalRecords;
    }

    public function hasMorePages()
    {
        if ($this->hasOwnMeta) {
            return $this->lastPage() > $this->currentPage();
        } else {
            return $this->apiLastPage();
        }
    }

    public function hasPerPage()
    {
        if ($this->hasOwnMeta) {
            return true;
        } else {
            return $this->apiHasPerPage();
        }
    }

    public function isFirstPage()
    {
        if ($this->hasOwnMeta) {
            return $this->currentPage() <= 1;
        } else {
            return $this->isFirstPage();
        }
    }

    public function totalRecords()
    {
        if ($this->totalRecords) {
            return $this->totalRecords;
        } else {
            return $this->apiTotalRecords();
        }
    }

    public function lastPage()
    {
        if ($this->hasOwnMeta) {
            return $this->lastPage;
        } else {
            return $this->apiLastPage();
        }
    }

    public function firstPage()
    {
        if ($this->hasOwnMeta) {
            return 1;
        } else {
            return $this->apiFirstPage();
        }
    }

    public function perPage()
    {
        if ($this->hasOwnMeta) {
            return $this->perPage;
        } else {
            return $this->apiPerPage();
        }
    }

    public function currentPage()
    {
        if ($this->hasOwnMeta) {
            return $this->currentPage;
        } else {
            return $this->apiCurrentPage();
        }
    }

    public function nextPageNumber()
    {
        if ($this->hasOwnMeta) {
            return $this->currentPage + 1;
        } else {
            return $this->apiNextPageNumber();
        }
    }

    public function previousPageNumber()
    {
        if ($this->hasOwnMeta) {
            return $this->currentPage - 1;
        } else {
            return $this->apiPreviousPageNumber();
        }
    }

    public function processRecordSweep()
    {
        if ($this->all || ! $this->builder->shouldPaginate()) {
            $this->recursiveRecordCollection();
        }
        $this->updateMeta();
        $this->resetQueryCount();
        $this->pageDownloaded();
    }

    public function recursiveRecordCollection()
    {
        if ($this->apiHasMorePages() && $this->canQuery()) {
            if ($this->apiHasPerPage()) {
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
        $this->perPage = $this->maxQueries * $this->apiPerPage;
        if ($this->apiTotalRecords != '') {
            $this->totalRecords = $this->apiTotalRecords;
            $this->lastPage = (int) ceil($this->totalRecords / $this->perPage);
            if ($this->lastPage == 1) {
                $this->currentPage = 1;
            } else {
                $this->currentPage = (int) floor($this->apiCurrentPage() / ($this->perPage / $this->apiPerPage()));
            }
        }
    }

    public function getNextRecords()
    {
        $this->processRecordSweep();

        return $this;
    }

    public function resetPage()
    {
        $this->items = new Collection;
    }

    public function nextPage()
    {
        if ($this->hasMorePages()) {
            $this->resetPage();
            if ($this->pageHasBeenDownloaded($this->nextPageNumber())) {
                $this->items = $this->getDownloadedPage($this->nextPageNumber());
                $this->currentPage = $this->nextPageNumber();
            } else {
                $this->processRecordSweep();
            }
        } else {
            throw new OutOfResultSetException();
        }

        return $this;
    }

    public function previousPage()
    {
        if (! $this->isFirstPage()) {
            $this->resetPage();
            if ($this->pageHasBeenDownloaded($this->previousPageNumber())) {
                $this->items = $this->getDownloadedPage($this->previousPageNumber());
                $this->currentPage = $this->previousPageNumber();
            }
        } else {
            throw new OutOfResultSetException();
        }

        return $this;
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
        $data = $this->itemsToArray();

        return [
            'current_page' => $this->currentPage(),
            'data' => $data,
            'last_page' => $this->lastPage(),
            'per_page' => $this->perPage(),
            'total' => $this->totalRecords(),
        ];
    }

    public function itemsToArray()
    {
        $data = [];
        foreach ($this->items as $item) {
            $data[] = $item->toArray();
        }

        return $data;
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
