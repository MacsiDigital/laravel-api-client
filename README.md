# Laravel package for Building API Client's

A little API Client Builder Library

## Installation

You can install the package via composer:

```bash
composer require macsidigital/laravel-api-client
```

## Usage

The main aim of this library is to add a common set of traits to models to be able to create, update, retrieve and delete records when accessing API's.  Obviously all API's are different so you should check documentation on how best to implement with these traits.

The basic concept is you build a client in the API library that you create which extends on the models in this library.

There are some authentication models included but its pretty simple to roll your own, Thanks to Taylor Otwell we now extend the new Laravel HTTP\Client library.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email colin@macsi.co.uk instead of using the issue tracker.

## Credits

- [Colin Hall](https://github.com/macsidigital)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
