<?php

namespace MacsiDigital\API\Support;

use BadMethodCallException;
use Illuminate\Support\Str;
use MacsiDigital\API\Contracts\Entry as EntryContract;
use MacsiDigital\API\Exceptions\NoClientSetException;
use MacsiDigital\API\Exceptions\NodeNotFoundException;
use MacsiDigital\API\Facades\Client;

abstract class Entry implements EntryContract
{
    // The Entry model is where we build our API gateway and set the defaults.
    // This should be extended in the core API, with a newRequest() method
    // that returns the client.
    
    protected $modelNamespace = '';

    // deafult query string names for page and per_page fields
    protected $perPageField = 'page_size';
    protected $pageField = 'page';

    // Should return raw responses and not models/resultsets
    protected $raw = false;

    // Shoule we throw exceptions in cases where a server error occurs
    protected $throwExceptionsIfRaw = false;

    // Should results be paginated by default.
    protected $pagination = true;

    // Amount of pagination results per page by default, leave blank if should not paginate
    // Without pagination rate limits could be hit
    protected $defaultPaginationRecords = '20';

    // Max and Min pagination records per page, will vary by API server
    protected $maxPaginationRecords = '100';
    protected $minPaginationRecords = '1';

    // If not paginated, how many queries should we allow per search, leave '' or 0
    // for unlimited queries. This of course will eat up any rate limits
    protected $maxQueries = '5';

    // Most API's should include pagination data - this is the fields we should be looking for
    // in the response to get this information.  We can use names or dot notation,
    // so for exmple 'current_page' or 'meta.current_page'
    protected $resultsPageField = 'meta.current_page';
    protected $resultsTotalPagesField = 'meta.last_page';
    protected $resultsPageSizeField = 'meta.per_page';
    protected $resultsTotalRecordsField = 'meta.total';

    // What operands are allowed when filtering
    protected $allowedOperands = ['=', '!=', '<', '>', '<=', '>=', '<>', 'like'];
    protected $defaultOperand = '=';

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        } else {
            try {
                return $this->$method;
            } catch (NodeNotFoundException $e) {
                throw new BadMethodCallException(sprintf(
                    'Call to undefined method %s::%s()',
                    static::class,
                    $method
                ));
            }
        }
    }

    public function __get($key)
    {
        return $this->getNode($key);
    }

    public function getBuilderClass()
    {
        return Builder::class;
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
        if (! $this->hasRequest()) {
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
        throw new NoClientSetException;
    }

    public function getPerPageField()
    {
        return $this->perPageField;
    }

    public function getPageField()
    {
        return $this->pageField;
    }

    public function getAllowedOperands()
    {
        return $this->allowedOperands;
    }

    public function getDefaultOperand()
    {
        return $this->defaultOperand;
    }

    public function getDefaultPaginationRecords()
    {
        if ($this->defaultPaginationRecords == '') {
            return 20;
        }

        return $this->defaultPaginationRecords;
    }

    public function getMaxPaginationRecords()
    {
        if ($this->maxPaginationRecords == '') {
            return 100;
        }

        return $this->maxPaginationRecords;
    }

    public function getMinPaginationRecords()
    {
        if ($this->minPaginationRecords == '') {
            return 1;
        }

        return $this->minPaginationRecords;
    }
    
    public function getPagination()
    {
        return $this->pagination;
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function getThrowExceptionsIfRaw()
    {
        return $this->throwExceptionsIfRaw;
    }

    public function hasMaxQueryLimit()
    {
        if ($this->maxQueries != '' && $this->maxQueries > 0) {
            return true;
        }

        return false;
    }

    public function getMaxQueries()
    {
        return $this->maxQueries;
    }

    public function getResultsPageField()
    {
        return $this->resultsPageField;
    }

    public function getResultsTotalPagesField()
    {
        return $this->resultsTotalPagesField;
    }

    public function getResultsPageSizeField()
    {
        return $this->resultsPageSizeField;
    }

    public function getResultsTotalRecordsField()
    {
        return $this->resultsTotalRecordsField;
    }
}
