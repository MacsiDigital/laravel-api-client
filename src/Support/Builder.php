<?php

namespace MacsiDigital\API\Support;

use Illuminate\Support\Str;
use MacsiDigital\API\Dev\Api;
use MacsiDigital\API\Exceptions\HttpException;
use MacsiDigital\API\Exceptions\ModelNotFoundException;

class Builder
{
    // Be good to add these:- findOrNew, firstOrNew, firstOrCreate and updateOrCreate
    // and these whereKey, latest, oldest, firstOr, whereBetween, whereNotBetween, whereNotIn, whereNull, whereNotNull, whereDate, whereMonth, whereDay, whereYear, whereTime
    // As we are not querying databases and API's vary greatly it may be that they need to be performed on the result set.
    protected $resource;
    protected $request;
    protected $queries = [];
    protected $wheres = [];
    protected $processedWheres = [];
    protected $orders = [];
    protected $limit = null;
    protected $offset = null;
    protected $raw = false;
    protected $throwExceptionsIfRaw = false;
    protected $paginate = false;

    protected $pageField;
    protected $perPageField;

    protected $perPage = '';
    protected $page = 1;

    protected $maxPerPage = '';
    protected $minPerPage = '';

    protected $allowedOperands;
    protected $defaultOperand;

    protected $asForm = false;
    protected $contentType = '';
    protected $files = [];

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
        $this->withExceptions($resource->client->getThrowExceptionsIfRaw());
        $this->setPaginate($resource->client->getPagination());
        if ($this->shouldPaginate()) {
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

    protected function setPageField($field)
    {
        $this->pageField = $field;

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

    public function attachFile($key, $file, $filename = "")
    {
        $this->files[$key] = ['file' => $file, 'filename' => $filename];

        return $this;
    }

    public function setContentType($type)
    {
        $this->contentType = $type;

        return $this;
    }

    public function asForm($boolean = true)
    {
        $this->asForm = $boolean;

        return $this;
    }

    protected function retreiveEndPoint($type = "get")
    {
        return $this->resource->getEndPoint($type);
    }

    protected function getApiDataField()
    {
        return $this->resource->getApiDataField();
    }

    public function find($id, $column = "")
    {
        if (is_array($id)) {
            return $this->whereIn($id, $column)->get(null, $raw);
        }

        return $this->handleResponse($this->sendRequest('get', [
            $this->retreiveEndPoint('find').'/'.$id,
            $this->combineQueries(),
        ]), "individual", "allow");
    }

    public function findMany(array $id, $column = "")
    {
        return $this->whereIn($id, $column)->get();
    }

    public function findOrFail($id)
    {
        return $this->handleResponse($this->sendRequest('get', [
            $this->retreiveEndPoint('get').'/'.$id,
        ]), "individual", "error");
    }

    public function firstOrFail()
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->resource));
    }

    protected function processHeaders()
    {
        if ($this->asForm) {
            $this->request->asForm();
        }
        if ($this->contentType != "") {
            $this->request->contentType($this->contentType);
        }
    }

    protected function processFiles()
    {
        if ($this->files != []) {
            foreach ($this->files as $key => $file) {
                $this->request->attach($key, $file['file'], $file['filename']);
            }
        }
    }

    public function sendRequest($method, $attributes)
    {
        $this->processHeaders();
        $this->processFiles();

        return $this->request->$method(...$attributes);
    }

    public function handleResponse($response, $type = "individual", $ifEmpty = "default")
    {
        if ($this->raw) {
            return $this->handleRaw($response);
        } elseif ($response->successful()) {
            return $this->{'process'.Str::studly($type).'Response'}($response);
        } elseif ($response->getStatusCode() == 404) {
            return $this->handle404($response, $ifEmpty);
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function handleRaw($response)
    {
        if (! $this->throwExceptionsIfRaw) {
            return $response;
        } elseif ($response->successful()) {
            return $response;
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function handle404($response, $ifEmpty)
    {
        if ($ifEmpty == 'allow') {
            return null;
        } elseif ($ifEmpty == 'error') {
            return $response->throw();
        } else {
            throw new HttpException($response->status(), $this->prepareHttpErrorMessage($response));
        }
    }

    public function all()
    {
        $this->setPerPageToMax();
        if ($this->resource->beforeQuery($this) === false) {
            return;
        }

        return $this->handleResponse($this->sendRequest('get', [
            $this->retreiveEndPoint('get'),
            $this->addPagination($this->combineQueries()),
        ]), 'all');
    }

    public function get($type = 'get')
    {
        if ($this->resource->beforeQuery($this) === false) {
            return;
        }

        return $this->handleResponse($this->sendRequest('get', [
            $this->retreiveEndPoint('get'),
            $this->addPagination($this->combineQueries()),
        ]), $type);
    }

    public function getOne()
    {
        return $this->get('individual');
    }

    public function post($attributes, $type = "individual")
    {
        if ($this->resource->beforePostQuery($this) === false) {
            return;
        }

        return $this->handleResponse($this->sendRequest('post', [
            $this->retreiveEndPoint('post'),
            $attributes,
            $this->combineQueries(),
        ]), $type.'Post');
    }

    public function patch($attributes, $type = "individual")
    {
        if ($this->resource->beforePatchQuery($this) === false) {
            return;
        }

        return $this->handleResponse($this->sendRequest('patch', [
            $this->retreiveEndPoint('patch'),
            $attributes,
            $this->combineQueries(),
        ]), $type.'Patch');
    }

    public function put($attributes, $type = "individual")
    {
        if ($this->resource->beforePutQuery($this) === false) {
            return;
        }

        return $this->handleResponse($this->sendRequest('put', [
            $this->retreiveEndPoint('put'),
            $attributes,
            $this->combineQueries(),
        ]), $type.'Put');
    }

    public function delete($type = "individual")
    {
        if ($this->resource->beforeDeleteQuery($this) === false) {
            return;
        }

        return $this->handleResponse($this->sendRequest('delete', [
            $this->retreiveEndPoint('delete'),
            $this->combineQueries(),
        ]), $type.'Delete');
    }

    public function first()
    {
        return $this->get()->first();
    }

    public function last()
    {
        return $this->get()->last();
    }

    public function firstWhere($column, $operand = null, $value = null)
    {
        $this->where($column, $operand, $value);

        return $this->first();
    }

    public function addQuery($key, $value)
    {
        $this->queries[$key] = $value;

        return $this;
    }

    public function whereRaw($column, $value)
    {
        $this->addQuery($column, $value);

        return $this;
    }

    public function where($column, $operand = null, $value = null)
    {
        if (is_array($column)) {
            foreach ($column as $query) {
                $this->where(...$query);
            }
        } else {
            if ($value == null) {
                $value = $operand;
                $operand = $this->getDefaultOperand();
            }
            $this->addWhere($column, $operand, $value);
        }

        return $this;
    }

    protected function addWhere($column, $operand, $value)
    {
        if ($this->operandAllowed($operand)) {
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

    public function whereIn(array $values, $column = "")
    {
        $string = implode(',', $values);
        if ($column == '') {
            $column = $this->resource->getKeyName().'s';
        }
        $this->addQuery($column, $string);

        return $this;
    }

    public function orderBy($value, $column = 'order')
    {
        $this->orders[$column] = $value;

        return $this;
    }

    public function count()
    {
        return $this->get()->count();
    }

    public function setPaginate($status = true)
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
        if ($page != null) {
            $this->setPage($page);
        }

        return $this;
    }

    public function setPerPage($perPage)
    {
        if ($this->maxPerPage != '' && $this->minPerPage > $perPage) {
            $perPage = $this->minPerPage;
        }
        if ($this->maxPerPage != '' && $this->maxPerPage < $perPage) {
            $perPage = $this->maxPerPage;
        }
        $this->perPage = $perPage;

        return $this;
    }

    public function setPerPageToMax()
    {
        $this->perPage = $this->maxPerPage;

        return $this;
    }

    public function setPerPageToMin()
    {
        $this->perPage = $this->minPerPage;

        return $this;
    }

    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    public function raw($status = true)
    {
        $this->raw = $status;

        return $this;
    }

    public function withExceptions($status = true)
    {
        $this->throwExceptionsIfRaw = $status;

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
        foreach ($this->wheres as $detail) {
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
        if ($this->perPage != '') {
            $array[$this->perPageField] = $this->perPage;
        }
        if ($this->page != '') {
            $array[$this->pageField] = $this->page;
        }

        return $array;
    }

    public function reset()
    {
        $this->resetQueries();
        $this->resetOrders();
        $this->resetLimit();
        $this->resetOffset();

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

    protected function processGetResponse($response)
    {
        return new ResultSet($this, $response, $this->resource);
    }

    protected function processAllResponse($response)
    {
        return new ResultSet($this, $response, $this->resource, true);
    }

    protected function processIndividualResponse($response)
    {
        if ($this->getApiDataField() != null) {
            $data = $response->json()[$this->getApiDataField()];
        } else {
            $data = $response->json();
        }
        if (isset($data[0])) {
            return $this->resource->newFromBuilder($this->resource->passOnAttributes($data[0]));
        } else {
            return $this->resource->newFromBuilder($this->resource->passOnAttributes($data));
        }
    }

    protected function processIndividualPostResponse($response)
    {
        if ($this->getApiDataField() != null) {
            $data = $response->json()[$this->getApiDataField()];
        } else {
            $data = $response->json();
        }
        if (isset($data[0])) {
            return $this->resource->updateFromBuilder($this->resource->passOnAttributes($data[0]));
        } else {
            return $this->resource->updateFromBuilder($this->resource->passOnAttributes($data));
        }
    }

    protected function processMultiPostResponse($response)
    {
        // if($this->getApiDataField() != null){
        //     $data = $response->json()[$this->getApiDataField()];
        // } else {
        //     $data = $response->json();
        // }
        // if(isset($data[0])){
        //     return $this->resource->newFromBuilder($this->resource->passOnAttributes($data[0]));
        // } else {
        //     return $this->resource->newFromBuilder($this->resource->passOnAttributes($data));
        // }
    }

    protected function processIndividualPatchResponse($response)
    {
        if ($this->getApiDataField() != null) {
            $data = $response->json()[$this->getApiDataField()];
        } else {
            $data = $response->json();
        }
        if (isset($data[0])) {
            return $this->resource->updateFromBuilder($this->resource->passOnAttributes($data[0]));
        } else {
            return $this->resource->updateFromBuilder($this->resource->passOnAttributes($data));
        }
    }

    protected function processMultiPatchResponse($response)
    {
        // if($this->getApiDataField() != null){
        //     $data = $response->json()[$this->getApiDataField()];
        // } else {
        //     $data = $response->json();
        // }
        // if(isset($data[0])){
        //     return $this->resource->newFromBuilder($this->resource->passOnAttributes($data[0]));
        // } else {
        //     return $this->resource->newFromBuilder($this->resource->passOnAttributes($data));
        // }
    }

    protected function processIndividualPutResponse($response)
    {
        if ($this->getApiDataField() != null) {
            $data = $response->json()[$this->getApiDataField()];
        } else {
            $data = $response->json();
        }
        if (isset($data[0])) {
            return $this->resource->updateFromBuilder($this->resource->passOnAttributes($data[0]));
        } else {
            return $this->resource->updateFromBuilder($this->resource->passOnAttributes($data));
        }
    }

    protected function processMultiPutResponse($response)
    {
        // if($this->getApiDataField() != null){
        //     $data = $response->json()[$this->getApiDataField()];
        // } else {
        //     $data = $response->json();
        // }
        // if(isset($data[0])){
        //     return $this->resource->newFromBuilder($this->resource->passOnAttributes($data[0]));
        // } else {
        //     return $this->resource->newFromBuilder($this->resource->passOnAttributes($data));
        // }
    }

    protected function processIndividualDeleteResponse($response)
    {
        return true;
    }

    protected function processMultiDeleteResponse($response)
    {
        return true;
    }

    public function prepareHttpErrorMessage($response)
    {
        return $response->json()['message'];
    }
}
