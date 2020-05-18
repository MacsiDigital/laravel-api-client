# Laravel package for Building API Client's

An API Client Builder Library

## Installation

You can install the package via composer:

```bash
composer require macsidigital/laravel-api-client
```

## Versions

1.0 - Laravel 5.5 - 6 - Bug fixes only, mainly there for backward compatibility, if there are any issues then create a pull request.

2.0 - Laravel 7.0 - Maintanined, again feel free to create pull requests.  This is open source which is a 2 way street.  Reason why it only uses Laravel 7 is because we hook into the new HTTP/Client in Laravel, thankfully created by Taylor Otwell.

## Usage

The main aim of this library is to add a common set of traits to models to be able to create, update, retrieve and delete records when accessing API's.  Obviously all API's are different so you should check documentation on how best to implement with these traits.

The basic concept is you build a client in the API library that you create which extends on the models in this library.

## Entry Model

The first thing to do is to create an entry model and extend the MacsiDigital/API/Support/Entry model, this is what shpaes how the API will work.  It's the model that houses all the attributes, so if you want pagination or to customise something it will likely be here.  Its an Abstract model so has to be extended in your implementation.

List of Attributes

```php
	// Where the models are
	protected $modelNamespace = '';

    // deafult query string names for page and per_page fields
    protected $perPageField = 'page_size';
    protected $pageField = 'page';

    // Should return raw responses and not models/resultsets
    protected $raw = false;

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
    protected $allowedOperands = ['='];
    protected $defaultOperand = '=';
```

In your extedned Entry model implementation you have to define a newRequest function, this is where the logic for how we are to connect will go and should return a Client object, which is the API Gateway Client.

```php
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

- get - retreive record(s)
- post - create a record
- patch - update a record
- put - replace a record
- delete - delete a record

However some API's have different ideas on what methods to call (we are looking at you Xero).

So you override the default methods by adding these attributes or methods to your models

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

Also some implementations, looking at you again Xero, use update methods to create models and create methods for updating models, so you will need to override the end point functions in the resource model. Xero uses a patch request for creating models and post request for updating models.

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

## Retreiving models

You can retrieve a single model by either using the find method and passing an ID, or an array of ID's, or by running a query and returning first();

```php
	$user = API::user()->find('ID');

	$user = API::user()->where('Name', 'Bob')->first();
```

You can also use get and all to retreive many models, this will return a Result Set, which is an enhanced Laravel Collection.

```php
	$users = API::account()->all();

	$users = API::account()->where('Type', 'Admin')->get();
```

## Result Sets

After we run a multi result return type like all() and get(), we are returned a result set.  This takes care of any pagination and can be used to dip back into the Gateway to retreive the next lot of results, houses information on what page we navigated, whats teh next page etc.  This is dependant on API and we try to utilise the API's returned meta where possible.

### all() return method

With the all() method we try to retrieve all results by recursivly hitting the API endpoint, because this can use rate limits quite quickly there is a $maxQueries attribute in our entry model, once set this is the max amount of queries that can be hit in a recursive call.

If the max is hit and you require more results then you can call the retreiveNextPage() method which will add the next round of queries to the result set.

### get() return method

This will return up to the max number of queries if its not set to paginate.  When pagination is set then it will restrict records to the perPage total.

We use get just like laravel, when we need to filter, order or paginate.

### Links

At present there is no facility to use predefined links but it is something we may add in the future.

## Raw Searches

If you would like to receive the raw response from the query then set raw on the query

```php
	$users = API::account()->raw()->all();

	$users = API::account()->where('Type', 'Admin')->raw()->get();
```

## Http Errors

If there are any errors returned from our call and the response is not set to raw then we will throw a new Http Exception.  We have some default behaviour but different API's have different responses to errors.  So you can override the prepareHttpErrorMessage() method on the builder model to customise the Exception message.

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

We have 2 base model types, resources and apiResources

### Resources

These are generally gesources that are returned as relationships of other models but do not interact with the API directly.  To create one of these extent the MacsiDigital/API/Support/Resource

### ApiResources

These are models that will interact directly with an API. To create one of these extent the MacsiDigital/API/Support/APIResource.

If you want to roll your own then you need to ensure you add the MacsiDigital\API\Traits\InteractsWithAPI trait.  Also follow how our Support API resource works.

In these models we also variosu info we store, like primary key and api endpoints, these are the available attributes

```php
	// These will map to RESTful requests
	// index -> get and all
	// create -> post
	// show -> first
	// update -> patch or put
	// delte -> delete
    protected $allowedMethods = ['index', 'create', 'show', 'update', 'delete'];

    protected $endPoint = 'user';

    protected $updateMethod = 'patch';

    protected $storeResource;
    protected $updateResource;

    protected $primaryKey = 'id';

    // Most API's return data in a data attribute.  However we need to override on a model basis as some like Xero return it as 'Users' or 'Invoices'
    protected $apiDataField = 'data'; 
```

In the case of Xero we can overwrite the getApiDatField function so that we dont have to set on all models.

```php
	public function getApiDataField()
    {
    	// Xero uses pluralised end points like 'Users' so we can use this to pick the data from responses
        return $this->endPoint;
    }
```

In a similar way we can override the priamryKey as some api's will keep the ID field as UserID by creating the following function

```php
	public function getKeyName()
    {
        return $this->endPoint.'ID';
    }
```

### Relationships

We have tried to get the models as close to a Laravel model as we can, even down to how relationships work.  

By default we will try to create relationship objects for any returned input if they are setup. However you can override this behaviour by setting LoadRaw to true in the model

```php
	protected $loadRaw = false;
```

There may be times where you want some autoloading but not allow, in this case you can set any models that should not autoload by setting them like so:-

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

A name and a field can be passed as a 2nd and 3rd argument.

We try to automatically work out the name and field attributes if not passed for you based on the function name, so we will look for a field 'user_id' in teh array 'users' in the above method. However not all API's use the same id naming so you can set the IDSuffix by adding this to your model.

```php
	protected $IdSuffix = 'ID';

	// Will now look for userID
```

Its worth pointing out that this is case sensitive so User and user will give different results, UserID and userID.  Again this is due to all API's being different.

Its also worth pointing out that some resources dont interact directly with the API, in these cases the field is ignored.

At present we do not have the ability for Many to Many but will see if there is a need in our API building quests.

Now that the relationships are set we can use Laravel like syntax to retrieve and work with models and relationships

```php
	$user = API::user()->find('id');

	$address = $user->address;

	// or you can also call the method for further filtering
	
	$address = $user->address()->where('type', 'billing')->first();
```

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

At present we only cover the where and whereIn, the latter will only work with the ID field, but we intend to add more of the laravel where clauses, like whereBetween if we find there is support for this in API's.

Now as noted all API's are different so generally we have to customise the filter logic.

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

As arrays cant use symbols as keys we do some translating, so it '=' is called we will change this to AddWhereEquals and processWhereEquals, the list below is of the trnslations we make.

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

Now each API will handle filtering differently so the logic in these methods are to suit the API, here is an example for xero, who add all filters to a where query string.  They allow the main ones and have 3 custom types, 'Contains' which is the same as a 'like' call, 'StartWith' and 'EndsWith'.

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

So as an example in Xero we can apply a If-Modified-Since header to our request to retrive only models modified since a specific date, this can be achieved like so.

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

Of course for these sorts of custom actions we have to update the allowed operands field within our Entry model to allow them to be apllied.

```php
	protected $allowedOperands = ['=', '!=', '<', '>', '<=', '>=', '<>', 'like', 'ModifiedAfter'];
```

## Creating and Updating

Generally in API's the attributes required for saving and updating are different, and they are in turn differnet to the attributes when a model is retreived.  We therefore utilise 'Persistance' models which will house validation logic and the attributes required to make a successful save.  So in our models we need to Extend the InteractsWithAPI trait and add the following 2 protected attributes.

```php
	protected $storeResource = 'MacsiDigital\API\Dev\Resources\StoreAddress';
    protected $updateResource = 'MacsiDigital\API\Dev\Resources\UpdateAddress';
```

The Store and Update resources should extend the MacsiDigital/API/Support/PersistResource.  These models will have $attributes for what fields to use and any validation.

```php
	protected $persistAttributes = [
    	'name' => 'required|string|max:255',
    	'email' => 'required|email|string|max:255',
    	'password' => 'required|string|max:10',
    ];
```
As you can see we set the field and any regular Laravel validation logic in the persistAttribute, if there is no validion we can pass ''.

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

When utilising the relationships in this way it will dip into that resource and use its validation and fields logic. So in this it will pull in the following logic

```php
	protected $persistAttributes = [
    	'street' => 'required|string|max:255',
    	'address2' => 'nullable|string|max:255',
    	'town' => 'required|string|max:255',
    	'county' => 'nullable|string|max:255',
    	'postcode' => 'required|string|max:10',
    ];

```

We can also require custom fields from relationships by using dot notation and not including the relationship:-

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

So in this instance we are only using street, town and postcode from the address model.

## Saving

To save its as simple as calling the save() function.

```php
	$user->save();
```
THe model will see if it already exists and call the correct update method.  If updating we will only pass dirty attributes to the persistance model.

You can also utilise other laravel methods like make, create and update directly in the model.

## Deleting

To delete we would just call the delete function, this will return true if it deleted or throw a HttpException if there was an error.

```php
	$user->delete();
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email security@macsi.co.uk instead of using the issue tracker.

## Credits

- [MacsiDigital](https://github.com/macsidigital)
- [ColinHall](https://github.com/colinhall17)
- [All Contributors](../../contributors)

We use a lot of Laravel type functions taken directly from Laravel, or taken and modified so we also have to credit the Laravel team.

- [Laravel](https://github.com/laravel)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
