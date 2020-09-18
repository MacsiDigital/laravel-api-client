# Laravel API Client

## Laravel package for Building API Client's

![Header Image](https://github.com/MacsiDigital/repo-design/raw/master/laravel-api-client/header.png)

<p align="center">
 <a href="https://github.com/MacsiDigital/laravel-api-client/actions?query=workflow%3ATests"><img src="https://github.com/MacsiDigital/laravel-api-client/workflows/Tests/badge.svg" style="max-width:100%;"  alt="tests badge"></a>
 <a href="https://packagist.org/packages/macsidigital/laravel-api-client"><img src="https://img.shields.io/packagist/v/macsidigital/laravel-api-client.svg?style=flat-square" alt="version badge"/></a>
 <a href="https://packagist.org/packages/macsidigital/laravel-api-client"><img src="https://img.shields.io/packagist/dt/macsidigital/laravel-api-client.svg?style=flat-square" alt="downloads badge"/></a>
</p>

An API Client Builder Library

## Installation

You can install the package via composer:

```bash
composer require macsidigital/laravel-api-client
```

## Versions

1.0 - Laravel 5.5 - 5.8 - Deprecated and no longer maintained.

2.0 - Laravel 6.0 - Maintained, again feel free to create pull requests.  This is open source which is a 2 way street.

3.0 - Laravel 7.0 - 8.0 - Maintained, again feel free to create pull requests.  This is open source which is a 2 way street.

## Usage

The main aim of this library is to add a common set of traits to models to be able to create, update, retrieve and delete records when accessing APIs.  Obviously all APIs are different, so you should check documentation on how best to implement with these traits.

The basic concept is you build a client in the API library that you create which extends on the models in this library.

## Entry Model

The first thing to do is to create an entry model and extend the MacsiDigital/API/Support/Entry model, this is what shapes how the API will work.  It's the model that houses all the attributes, so if you want pagination or to customise something it will likely be here.  Its an Abstract model so has to be extended in your implementation.

We recommend placing it in a Support folder within your src directory.

List of Attributes

```php
	// Where the models are
	protected $modelNamespace = '';

    // default query string names for page and per_page fields
    protected $perPageField = 'page_size';
    protected $pageField = 'page';

    // Should return raw responses and not models/resultsets
    protected $raw = false;

    // Should return raw responses and not models/resultsets
    protected $raw = false;

    // Should we throw exceptions in cases where a server error occurs
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

    // Most APIs should include pagination data - this is the fields we should be looking for
    // in the response to get this information.  We can use names or dot notation,
    // so for example 'current_page' or 'meta.current_page'
    protected $resultsPageField = 'meta.current_page';
    protected $resultsTotalPagesField = 'meta.last_page';
    protected $resultsPageSizeField = 'meta.per_page';
    protected $resultsTotalRecordsField = 'meta.total';

    // What operands are allowed when filtering
    protected $allowedOperands = ['=', '!=', '<', '>', '<=', '>=', '<>', 'like'];
    protected $defaultOperand = '=';
```

Most of these are self-explanatory, there is a magic method attribute in there, if we return raw data than we receive the actual response without any error checking.  if we set $throwExceptionsIfRaw = true, then we still receive raw data, but it will check to make sure the return was successful and not an error. If we receive an error it will throw a HttpException.  You can also set this on teh fly by calling with Exceptions() on a query.

In your extended Entry model implementation you have to define a newRequest function, this is where the logic for how we 1connect will go and should return a MacsiDigital\API\Support\Factory object, which is better renamed to Client in your implementation, which will resolve the API Gateway Client.

```php
namespace MacsiDigital\Package\Support;

use MacsiDigital\Package\Facades\Client; // This should extend the MacsiDigital\API\Support\Factory
use MacsiDigital\API\Support\Entry as ApiEntry;

class Entry extends ApiEntry
{
    protected $modelNamespace = '\MacsiDigital\Package\\';

    // Change any attributes to match API
 
	public function newRequest()
    {
        return Client::baseUrl(config('api.base_url'))->withOptions(config('api.options'));
    }
```

If using OAuth we would have to input our OAuth Logic, or for OAuth2 something like this would work, this is xero implementation

```php
	public function newRequest()
    {
        $config = config('xero');
    	$class = $config['tokenModel'];
    	$token = new $class('xero');
    	if($token->hasExpired()){
    		$token = $token->renewToken();
    	}
        return Client::baseUrl($config['baseUrl'])->withToken($token->accessToken())->withHeaders(['xero-tenant-id' => $token->tenantId()]);
    }
```

As you can see all the initial logic is required on how we need to open up a Gateway, please note that the API does not handle the OAuth2 Authorisation, for this you will need to use an OAuth2 client like macsidigital/laravel-oauth2-client or league/oauth2-client.  The macsidigital client handles the routing and saving of the data to either database or file.

There is also a function in the entry class that returns the builder class to use, if you roll your own builder class, further details below, this is where you need to set the class.

```php
	public function getBuilderClass() 
    {
        return Builder::class; // By default links to MacsiDigital/API/Support/Builder
    }
```

## RESTful API by default

We by default use standard RESTful method calls:-

- get - retrieve records
- post - create a record
- patch - update a record
- put - replace a record
- delete - delete a record

We also add a find call, as most APIs have different endpoints for get and find methods.

- find - retrieve a single record

However, some APIs have different ideas on what methods to call (we are looking at you Xero).

So you override the default create and update methods by adding these attributes or methods to your models

```php
	protected $createMethod = 'post';

    protected $updateMethod = 'patch';

	public function getUpdateMethod()
    {
        return $this->updateMethod;
    }

    public function getCreateMethod()
    {
        return $this->createMethod;
    }
```

Also, some implementations, looking at you again Xero, use update methods to create models and create methods for updating models, so you may need to override the end point functions in the resource model. We have added logic to try to automatically get around this but if you are having problems then set them manually in your model.

Xero uses a patch request for creating models and post request for updating models.

```php
	//Normal
	public function getPostEndPoint() 
    {
        return $this->endPoint;
    }

    public function getPutEndPoint() 
    {
        return $this->endPoint.'/'.$this->getKey();
    }

    //Xero
	public function getPostEndPoint() 
    {
        return $this->endPoint.'/'.$this->getKey();
    }

    public function getPutEndPoint() 
    {
        return $this->endPoint;
    }
```

## Retrieving models

You can retrieve a single model by either using the find method and passing an ID, or an array of IDs, or by running a query and returning first() or last();

```php
	$user = API::user()->find('ID');

	$user = API::user()->where('Name', 'Bob')->first(); // First occurrence

    $user = API::user()->where('Name', 'Bob')->last(); // Last occurrence
```

You can also use get and all to retrieve many models, this will return a Result Set, which is an enhanced Laravel Collection.

```php
	$users = API::account()->all();

	$users = API::account()->where('Type', 'Admin')->get();
```

Some APIs (Yes Xero) return single results wrapped in an array, like multi record searches.  So you can override the hydrate() method on the builder model.

```php
	protected function hydrate($response)
    {
        return $this->resource->newFromBuilder($response->json()[$this->getApiDataField()][0]);
    }
```

## Result Sets

After we run a multi result return type like all() and get(), we are returned a result set.  This will take care of any pagination and can be used to dip back into the Gateway to retrieve the next lot of results, houses information on what page we navigated, what is the next page etc.  This is dependant on the API, and we try to utilise the APIs returned meta where possible.

### all() return method

With the all() method we try to retrieve all results by recursively hitting the API endpoint, because this can use rate limits quite quickly there is a $maxQueries attribute in our entry model, once set this is the max amount of queries that can be hit in a recursive call.

If the max is hit and you require more results then you can call the retrieveNextPage() method which will add the next round of queries to the result set.

### get() return method

This will return up to the max number of queries if its not set to paginate.  When pagination is set then it will restrict records to the perPage total.

We use get() just like laravel, when we need to filter, order or paginate.

### Result Set Functions

As noted result sets are just like laravel collections, with additional functions.

So if our API only returns 30 results per page, our all method will do its best to return as many results as it can.  This will generally lead to some pagination.  So to retrieve the next set of results we can do the following.

```php
    $meetings = $user->meetings;
    // Do some logic and discover need more results
    $meetings = $meetings->nextPage();

    // $meetings->previousPage() will go back a page.

    // We can also iterate directly over the returned results
    foreach($meetings->nextPage() as $meeting)

    //Finally for those using json api in SPA app, you can utilise the toArray or toJson functions
    $meetings->toArray();

    // returns 
    array:5 [
      "current_page" => 1
      "data" => array:2 [
        0 => array:10 [
          // $attributes
        ]
        1 => array:10 [
          // $attributes
        ]
      ]
      "last_page" => 5
      "per_page" => 30
      "total" => 137
    ]
```

The previousPage() method will go back a page, we cache the results so going back will not make an api call but pull the cached results.

Sometimes you may also want to accumulate records, to do this you can call the getNextRecords() method.  Please note not to mix this method with the next and previous page methods.

```php
    // will add more records to the current record set. The amount of records retrieved is based on the maxQueries and per page methods.
    $meetings->getNextRecords();
```

// Need to check this as think Xero doesn't return page counts.
If you try to retrieve more records than there is available, then an exception will be thrown.

### Links

At present there is no facility to use predefined links but it is something we may add in the future.

## Raw Searches

If you would like to receive the raw response from the query then set raw on the query

```php
	$users = API::account()->raw()->all();

	$users = API::account()->where('Type', 'Admin')->raw()->get();
```

## Http Errors

If there are any errors returned from our call and the response is not set to raw then we will throw a new Http Exception.  We have some default behaviour but different APIs have different responses to errors.  So you can override the prepareHttpErrorMessage() method on the builder model to customise the Exception message.

```php
	$json = $response->json();
	if($json['Type'] == 'ValidationException'){
		$message = $json['Message'];
		foreach($json['Elements'][0]['ValidationErrors'] as $error){
			$message .= ' : '.$error['Message'];
		}
    	return $message;
	} else {
    	return $json['Message'];
	}
```

## Models

We have 2 base model types, resources and apiResources.

We utilise Laravel's hasAttributes trait in the models so you should be able to use any casts like Laravel in any model.

### Resources

These are generally resources that are returned as relationships of other models but do not interact with the API directly.  To create one extend the MacsiDigital/API/Support/Resource.

This should only be used on models that are returned as a sub array of a called model.

### API Resources

These are models that will interact directly with an API, or indirectly through a parent model. To create one extend the MacsiDigital/API/Support/APIResource.

If you want to roll your own then you need to ensure you add the MacsiDigital\API\Traits\InteractsWithAPI trait.  Also follow how our Support API resource works.

In these models we also house many variables, like primary key and api endpoints, these are the available attributes

```php
	// These will map to RESTful requests
	// index -> get and all
	// create -> post
	// show -> first
	// update -> patch or put
	// delete -> delete
    protected $allowedMethods = ['index', 'create', 'show', 'update', 'delete'];

    protected $endPoint = 'user';

    protected $updateMethod = 'patch';

    protected $storeResource;
    protected $updateResource;

    protected $primaryKey = 'id';

    // Most APIs return data in a data attribute.  However we need to override on a model basis as some like Xero return it as 'Users' or 'Invoices'
    protected $apiDataField = 'data'; 

    // Also, some APIs return 'users' for multiple and user for single, set teh multiple field below to wheat is required if different
    protected $apiMultipleDataField = 'data'; 
```
The apiData and apiMultiple fields dictate what field in the response will house the main body data, this should be 'data' in a good api but it really does vary.  Zoom uses 'users' for multiple records and '' for single.

If apiDataField is set to '' it will return the body direct.

```php
    protected $apiDataField = '';
```

Xero also uses a different method.

In the case of Xero it can be overwritten in the getApiMultipleDataField function so that we don't have to set on all models.

```php
	public function getApiMultipleDataField()
    {
    	// Xero uses pluralised end points like 'Users' so we can use this to pick the data from responses
        return $this->endPoint;
    }
```

In a similar way we can override the primaryKey as some api's will keep the ID field as UserID by creating the following function

```php
	public function getKeyName()
    {
        return $this->endPoint.'ID';
    }
```

### EndPoints

A quick note on endpoints, we can set an endpoint as 'users' but we can also include bound models similar to a laravel route when results are returned as part of a relationship.

```php
    protected $endPoint = 'users/{user:id}/settings';
```

We can also set customEndpoints on a model for specific endPoints, if the endPoints don't follow convention.

```php
    protected $customEndPoints = [
        'get' => 'users/{user:id}/meetings',
        'post' => 'users/{user:id}/meetings'
    ];
```

### Relationships

We have tried to get the models as close to a Laravel model as we can, even down to how relationships work.

By default we will try to create relationship objects for any returned input if they are setup. However you can override this behaviour by setting LoadRaw to true in the model

```php
	protected $loadRaw = false;
```

There may be times where you want some auto-loading but not allow all connected models to autoload, in this case you can set any models that should not autoload by setting them like so:-

```php
	protected $dontAutoloadRelation = ['Address'];
```

For each model we need to create a function, just like Laravel, so if there is a User Model which has a relationship with an Address Model you would set:-

```php
	public function address() 
    {
    	return $this->hasOne(Address::class);
    }
```

This could also be a HasMany relationship and the reverse on the address would be a belongsTo method

```php
	public function user() 
    {
    	return $this->belongsTo(User::class);
    }
```

A name and a field can be passed as a 2nd and 3rd argument.  A 4th argument can also passed which will be any fields and values in an array that should be passed onto all relationship models.  This is handy when you need to track a parent's id on teh child model.

We try to automatically work out the name and field attributes if not passed for you based on the function name, so we will look for a field 'user_id' in the array 'users' in the above method. However not all APIs use the same id naming so you can set the IDSuffix by adding this to your model.

```php
	protected $IdSuffix = 'ID';

	// Will now look for userID instead of user_id
```

With the name field we will check results for User and user

Its worth pointing out that this is case sensitive so User and user will give different results, UserID and userID.  Again this is due to all APIs being different.

Its also worth pointing out that some resources don't interact directly with the API, in these cases the field is ignored.

At present we do not have the ability for Many to Many but will see if there is a need in our API building quests.

Now that the relationships are set we can use Laravel like syntax to retrieve and work with models and relationships

```php
	$user = API::user()->find('id');

	$address = $user->address;

	// or you can also call the method for further filtering
	
	$address = $user->address()->where('type', 'billing')->first();
```

We utilise save, saveMany (has many only), create and createMany (has many only) functions to save existing models and create new models directly to the relation, as long as they are models that interact with the api.

```php
    // save

    $user = API::user()->find('id');

    $address = Api::address()->find('id');
    
    $user->address()->save($address);

    // create

    $user = API::user()->find('id');
    
    $user->address()->create([
        'address1' => '17 Test Street',
        ...
    ]);
```

When the relation models do not interact with the api then we expose new make() and attach() methods so they can be attached to persisting models for saving.

```php
    $user = API::user()->find('id');
    
    $user->address()->make([
        'address1' => '21 Test Street',
        ....
    ]);

    $user = API::user()->find('id');

    $address = Address::make([...]);

    // Do some logic checks
    
    $user->address()->attach($address);
```

This works well when relationships are returned direct as part of an API call, which is common in APIs.  However sometimes APIs don't send these items direct and therefore need different end point calls.

In these cases we use custom relationship models

### Custom Relationship Models

First you need to extend either the HasOne or HasMany relationship models.

Within the extended model you need to create the logic for retrieving, creating, updating and deleting as is required by the API.  Unfortunately as these are all case specific you will need to create CustomRelations models for any implementation required.

To call it we call the hasCustom method and pass the model class as the 2nd parameter.  Parameters 3 and 4 can still be the name and field and 5 any fields and values to add to any new models.

```php
public function addresses()
{
    $this->hasCustom(Address::class, addressHasMany::class);

}
```

The custom class will need a constructor, a save, saveMany (for hasMany), create, createMany (for hasMany) and getResults methods.

This really gives a blank canvas to be able to get those pain in the butt endpoints into your API.

Sometimes relationships are not returned as part of the request and have to be called separately.  Sometimes this may mean hitting a different endpoint, which is set in the model, see note on endPoints above.

We can also override the default with customEndPoints on a per model basis.

```php
    protected $customEndPoints = [
        'get' => 'users/{user:id}/meetings',
        'post' => 'users/{user:id}/meetings'
    ];
```

You can set end points for find, get, create, update and delete.

## Filters

Filters will vary from API to API, so we have tried to build an extendable filter functionality that can easily be implemented.

Filters use Laravel like syntax to apply with the where functions

```php
	$user = API::user()->where('Name', 'John Doe')->first();

	$users = API::user()->where('Name', '!=', 'John Doe')->get();

	$posts = API::post()->where('Title', 'like', 'Mechanical')->get();
```

And of course you can stack where clauses

```php
	$posts = API::post()->where('created_at', '>', '2019-01-01')->where('created_at', '<', '2019-12-31')->get();
```

At present we only cover the where and whereIn, the latter will only work with the ID field, but we intend to add more of the laravel where clauses, like whereBetween if we find there is support for this in APIs.

Now as noted all APIs are different so generally we have to customise the filter logic.

First of all you need to create a Builder model that extends the Support/Builder model.

In the Entry model you need to set the below function

```php
	public function getBuilderClass() 
    {
        return \Namespace\To\Your\Builder::class; 
    }
```

In this builder class you would then create functions that are called when filters are added and when they are processed into the query string. So to modify we would modify 2 methods

```php
	protected function addWhereEquals($column, $operand, $value)
    {
        $this->wheres[] = ['column' => $column, 'operand' => $operand, 'value' => $value];
    }

    public function processWhereEquals($detail) // Passed in the array attached in above method
    {
        $this->processedWheres[$detail['column']] = $detail['value'];
    }
```

As arrays cant use symbols as keys we do some translating, so if '=' is called we will change this to AddWhereEquals and processWhereEquals, the list below is of the translations we make.

```php
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
	    return 'Process'.Str::studly($operand);
```

The default is called for any custom filters, so lets say you call where('name', 'StartsWith', 'Bob') this will call addWhereStartsWith and processWhereStartsWith methods

Now each API will handle filtering differently so the logic in these methods are to suit the API, here is an example for Xero, who add all filters to a where query string.  They allow the main ones and have 3 custom types, 'Contains' which is the same as a 'like' call, 'StartWith' and 'EndsWith'.

```php
	public function processEquals($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'=="'.$detail['value'].'"';
		
	}

	public function processNotEquals($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'!="'.$detail['value'].'"';
	}

	public function processGreaterThan($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'>"'.$detail['value'].'"';
	}

	public function processGreaterThanOrEquals($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'>="'.$detail['value'].'"';
	}

	public function processLessThan($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'<"'.$detail['value'].'"';
	}

	public function processLessThanOrEquals($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'<="'.$detail['value'].'"';
	}

	public function processGreaterThanOrLessThan($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'<>"'.$detail['value'].'"';
	}

	public function processContains($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'.Contains("'.$detail['value'].'")';
	}

	public function processStartsWith($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'.StartsWith("'.$detail['value'].'")';
	}

	public function processEndsWith($detail) 
	{
		if(!isset($this->processedWheres['where'])){
			$this->processedWheres['where'] = '';
		}
		$this->processedWheres['where'] .= $detail['column'].'.EndsWith("'.$detail['value'].'")';
	}
```

As we create custom filter methods, this makes filtering easy and extremely powerful, but it really does depend on what the API allows to be done.

So as an example in Xero we can apply a If-Modified-Since header to our request to retrieve only models modified since a specific date, this can be achieved like so.

```php
	// you would need to spoof the column name, as its not used but is still required. 
	// So call something like
	$user->where('UpdatedDate', 'ModifiedAfter', $date)->get();

	public function addWhereModifiedAfter($column, $operand, $value) 
	{
		$this->wheres[] = ['operand' => $operand, 'value' => $value];
	}

	public function processWhereModifiedAfter($detail) 
	{
		$this->request->withHeader([
			'If-Modified-Since' => $detail['value']
		]);
	}
```

Of course for these sorts of custom actions we have to update the allowed operands field within our Entry model to allow them to be applied.

```php
	protected $allowedOperands = ['=', '!=', '<', '>', '<=', '>=', '<>', 'like', 'ModifiedAfter'];
```

## Creating and Updating

Generally in APIs the attributes required for saving and updating are different, and they are in turn different to the attributes when a model is retrieved.  We therefore utilise 'Persistence' models which will house validation logic and the attributes required to make a successful save.  So in our models we need to Extend the InteractsWithAPI trait and add the following 2 protected attributes.

```php
	protected $insertResource = 'MacsiDigital\API\Dev\Resources\StoreAddress';
    protected $updateResource = 'MacsiDigital\API\Dev\Resources\UpdateAddress';
```

The Insert and Update resources should extend the MacsiDigital/API/Support/PersistResource.  These models will have attributes for what fields to use and any validation.

```php
	protected $persistAttributes = [
    	'name' => 'required|string|max:255',
    	'email' => 'required|email|string|max:255',
    	'password' => 'required|string|max:10',
    ];
```
As you can see we set the field and any regular Laravel validation logic in the persistAttribute, if there is no validation we can pass ''.

We can also extend this to include relationships.  It may look like so:-

```php
	protected $persistAttributes = [
    	'name' => 'required|string|max:255',
    	'email' => 'required|email|string|max:255',
    	'password' => 'required|string|max:10',
    ];

    protected $relatedResource = [
    	'address' => '\MacsiDigital\API\Dev\Resources\UpdateAddress'
    ];
```

When utilising the relationships in this way it will dip into the linked resource model and use its validation and fields logic. So in this it will pull in the following logic

```php
	protected $persistAttributes = [
    	'street' => 'required|string|max:255',
    	'address2' => 'nullable|string|max:255',
    	'town' => 'required|string|max:255',
    	'county' => 'nullable|string|max:255',
    	'postcode' => 'required|string|max:10',
    ];

```

We can pass an array directly into the relatedResource attribute if we don't want to create a persistent resource.

```php
    protected $relatedResource = [
    	'address' => [
            'street' => 'required|string|max:255',
    		'address2' => 'nullable|string|max:255',
    		'town' => 'required|string|max:255',
    		'county' => 'nullable|string|max:255',
    		'postcode' => 'required|string|max:10',
        ],
    ];
```

We can require fields from relationships by using dot notation and not including the relationship:-

```php
	protected $persistAttributes = [
    	'name' => 'required|string|max:255',
    	'email' => 'required|email|string|max:255',
    	'password' => 'required|string|max:10',
    	'address.street' => 'required|string|max:255',
    	'address.town' => 'required|string|max:255',
    	'address.postcode' => 'required|string|max:10',
    ];
```

So in this instance we are only interested in street, town and postcode from the address model.

We can use a mix of the above, its not one or the other.

### Mutating

Some APIs will mutate the data, a common case is wrapping the fields in a new array.  

```php
    //Normal create
   [
        'name' => 'John Doe',
        'email' => 'john@Example.com'
   ] 

   // Some APIs may require
   // 
   [
        'action' => 'create',
        'user_info' => [
            'name' => 'John Doe',
            'email' => 'john@Example.com'
        ]
   ]
```

To achieve this we can use Mutators on the persistModel

```php
    protected $persistAttributes = [
        'action' => 'required|string|in:create,autoCreate,custCreate,ssoCreate',
        'user_info.name' => 'required|string|max:255',
        'user_info.email' => 'required|email|string|max:255',
    ];

    // To apply mutators we set what teh normal key is and what we want it to end up as
    protected $mutateAttributes = [
        'name' => 'user_info.name',
        'email' => 'user_info.email'
        'type' => 'status' //can also use to mutate current attributes into attributes for the api
    ] 

```

This will now create the correct json for the api and allow validation to still take place.

## Saving

To save its as simple as calling the save() function.

```php
	$user->save();
```

THe model will see if it already exists and call the correct insert or update method.  If updating we will only pass dirty attributes to the persistence model.

You can also utilise other laravel methods like make, create and update directly in the model.

We pass back helpful exceptions if it fails due to the API rejecting it. For example some APIs will have unusual rules, in Xero you can only pass multiple contacts if you supply email addresses, in this case we would get back an exception:-

```bash
MacsiDigital/API/Exceptions/HttpException with message 'A validation exception occurred : Additional people cannot be added when the primary person has no email address set.'
```

## Deleting

To delete we would just call the delete function, this will return true if it deleted or throw a HttpException if there was an error.

```php
	$user->delete();
```

## Calling custom requests

Whilst we do our best to cover the main restful endPoints for models, there are going to be some cases where we have to create something custom.

We have tried to make that as easy as possible, each model has the API client injected in on instantiation, so we can create any function to call on the API.  For example, zoom exposes an endPoint to upload a profile picture, we could create persistModels, but it may be overkill, so we can simply create a function like so

```php
    public function updateProfilePicture($image)
    {
        $filesize = number_format(filesize($image) / 1048576,2);
        if($filesize > 2){
            throw new FileTooLargeException($image, $filesize, '2MB');
        } else {
            return $this->newQuery()->attachFile('pic_file', file_get_contents($image), $image)->sendRequest('post', ['users/'.$this->id.'/picture'])->successful();
        }
    }
```
Nice and simple.

## ToDo

- Proper Documentation
- Tests

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email [info@macsi.co.uk](mailto:info@macsi.co.uk) instead of using the issue tracker.

## Credits

- [Colin Hall](https://github.com/colinhall17)
- [MacsiDigital](https://github.com/MacsiDigital)
- [All Contributors](../../contributors)

We use a lot of Laravel type functions taken directly from Laravel, or taken and modified so we also have to credit the Laravel team.

- [Laravel](https://github.com/laravel)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
