<?php

namespace MacsiDigital\API\Support;

use Illuminate\Support\Str;
use MacsiDigital\API\Dev\Api;
use Illuminate\Support\Collection;
use MacsiDigital\API\Support\ResultSet;
use MacsiDigital\API\Exceptions\HttpException;

class Builder
{
    protected $resource;
    protected $request;
    protected $queries = [];
    protected $wheres = [];
    protected $processedWheres = [];
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

    protected $allowedOperands;
    protected $defaultOperand;


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
        $this->setPaginate($resource->client->getPagination());
        if($this->shouldPaginate()){
            $this->paginate($resource->client->getDefaultPaginationRecords());
        }
        $this->setMaxPerPage($resource->client->getMaxPaginationRecords());
        $this->setMinPerPage($resource->client->getMinPaginationRecords());

        $this->setAllowedOperands($resource->client->getAllowedOperands());
        $this->setDefaultOperand($resource->client->getDefaultOperand());
    }

    protected function setAllowedOperands(array $array)
    {
        $this->allowedOperands = $array;
        return $this;
    }

    protected function getAllowedOperands()
    {
        return $this->allowedOperands;
    }

    protected function getOperandTranslation($operand)
    {
        switch ($operand) {
            case '=':
                return 'Equals';
            case '!=':
                return 'NotEquals';
            case '>':
                return 'GreaterThan';
            case '>=':
                return 'GreaterThanOrEquals';
            case '<':
                return 'LessThan';
            case '<=':
                return 'LessThanOrEquals';
            case '<>':
                return 'GreaterThanOrLessThan';
            case 'like':
                return 'Contains';
            default:
                return Str::studly($operand);
        }
    }

    protected function operandAllowed($operand)
    {
        return in_array($operand, $this->getAllowedOperands());
    }

    protected function getDefaultOperand()
    {
        return $this->defaultOperand;
    }

    protected function setDefaultOperand($default)
    {
        $this->defaultOperand = $default;
        return $this;
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

    protected function retreiveEndPoint($type="get")
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
        $response = $this->request->get($this->retreiveEndPoint('get').'/'.$id, $this->combineQueries());
        if($this->raw){
            return $response;
        }
        if($response->ok()){
            return $this->hydrate($response);
        } else if($response->getStatusCode() == 404) {
            return null;
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function findOrFail($id) 
    {
        $response = $this->request->get($this->retreiveEndPoint('get').'/'.$id, $this->combineQueries());
        if($this->raw){
            return $response;
        }
        if($response->ok()){
            return $this->hydrate($response);
        } else if($response->getStatusCode() == 404) {
            return $response->throw();
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function all() 
    {
        $response = $this->request->get($this->retreiveEndPoint('get'), $this->addPagination($this->combineQueries()));
        if($this->raw){
            return $response;
        }
        if($response->ok()){
            return $this->processAllResultSet($response);
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function get()
    {
        if($this->data != null){
            return $this->data;
        }
        $response = $this->request->get($this->retreiveEndPoint('get'), $this->addPagination($this->combineQueries()));
        if($this->raw){
            return $response;
        }
        if($response->ok()){
            return $this->data = $this->processResultSet($response);
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function post($attributes)
    {
        $response = $this->request->post($this->retreiveEndPoint('post'), $attributes, $this->combineQueries());
        if($this->raw){
            return $response;
        }
        if($response->successful()){
            return $this->hydrate($response, false);
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function patch($attributes)
    {
        $response = $this->request->patch($this->retreiveEndPoint('patch'), $attributes, $this->combineQueries());
        if($this->raw){
            return $response;
        }
        if($response->successful()){
            return $this->hydrate($response);
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function put($attributes)
    {
        $response = $this->request->put($this->retreiveEndPoint('put'), $attributes, $this->combineQueries());
        if($this->raw){
            return $response;
        }
        if($response->successful()){
            return $this->hydrate($response);
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function delete() 
    {
        $response = $this->request->delete($this->retreiveEndPoint('delete'), $this->combineQueries());
        if($this->raw){
            return $response;
        }
        if($response->failed()){
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
        return true;
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

    public function query($column, $value) 
    {
        $this->queries[$column] = $value;
    }
    
    public function where($column, $operand = null, $value = null)
    {
        if($this->data != null){
            $this->data = null;
        }
        if(is_array($column)){
            foreach($column as $query){
                $this->where(...$query);
            }
        } else {
            if($value == null){
                $value = $operand;
                $operand = $this->getDefaultOperand();
            }
            $this->addWhere($column, $operand, $value);
        }
        return $this;
    }

    protected function addWhere($column, $operand, $value){
        if($this->operandAllowed($operand)){
            $function = 'addWhere'.$this->getOperandTranslation($operand);
            $this->$function($column, $operand, $value);
        }
    }

    protected function addWhereEquals($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    protected function addWhereNotEquals($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    protected function addWhereGreaterThan($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    protected function addWhereGreaterThanOrEquals($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    protected function addWhereLessThan($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    protected function addWhereLessThanOrEquals($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    protected function addWhereGreaterThanOrLessThan($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    protected function addWhereContains($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    public function whereIn(array $values, $column="") 
    {
        $string = implode(',', $values);
        return $this->queries($column, $string)->get();
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

    public function setPaginate($status=true) 
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

    public function paginate($perPage, $page = null)
    {
        $this->setPaginate(true);
        $this->setPerPage($perPage);
        if($page != null){
            $this->setPage($page);
        }
        return $this;
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

    protected function combineQueries() 
    {
        return array_merge($this->processQueries(), $this->processOrders());
    }

    protected function processQueries()
    {
        $this->processWheres();
        return array_merge($this->queries, $this->processedWheres);
    }

    protected function processWheres()
    {
        foreach($this->wheres as $detail){
            $function = 'ProcessWhere'.$this->getOperandTranslation($detail['operand']);
            $this->$function($detail);
        }
        return $this;
    }

    public function processWhereEquals($detail) 
    {
        $this->processedWheres[$detail['column']] = $detail['value'];
    }

    public function processWhereNotEquals($detail) 
    {
        $this->processedWheres[$detail['column']] = '-'.$detail['value'];
    }

    protected function processWhereGreaterThan($detail)
    {
        $this->processedWheres[$detail['column']] = '>'.$detail['value'];
    }

    protected function processWhereGreaterThanOrEquals($detail)
    {
        $this->processedWheres[$detail['column']] = '>='.$detail['value'];
    }

    protected function processWhereLessThan($detail)
    {
        $this->processedWheres[$detail['column']] = '<'.$detail['value'];
    }

    protected function processWhereLessThanOrEquals($detail)
    {
        $this->processedWheres[$detail['column']] = '<='.$detail['value'];
    }

    protected function processWhereGreaterThanOrLessThan($detail)
    {
        $this->processedWheres[$detail['column']] = '<>'.$detail['value'];
    }

    protected function processWhereContains($detail)
    {
        $this->processedWheres[$detail['column']] = '%'.$detail['value'].'%';
    }

    protected function processOrders()
    {
        return $this->orders;
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
        $this->wheres = [];
        $this->processedWheres = [];
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

    protected function hydrate($response)
    {
        return $this->resource->newFromBuilder($response->json()[$this->getApiDataField()]);
    }

    public function prepareHttpErrorMessage($response)
    {
        return $response->json()['message'];
    }

}