# laravel-grapql-relay #

Use Facebook [GraphQL](http://facebook.github.io/graphql/) with [React Relay](https://facebook.github.io/relay/). This package extends graphql-php to work with Laravel and is currently **a work in progress**. You can reference what specifications GraphQL needs to provide to work with Relay in the [documentation](https://facebook.github.io/relay/docs/graphql-relay-specification.html#content).

### Installation ###

You must then modify your composer.json file and run composer update to include the latest version of the package in your project.

```php
"require": {
    "nuwave/laravel-graphql-relay": "0.3.*"
}
```

Or you can use the composer require command from your terminal.

```
composer require nuwave/laravel-graphql-relay
```

Add the service provider to your ```app/config.php``` file

```
Nuwave\Relay\LaravelServiceProvider::class
```

Add the Relay & GraphQL facade to your app/config.php file

```
'GraphQL' => Nuwave\Relay\Facades\GraphQL::class,
'Relay' => Nuwave\Relay\Facades\Relay::class,
```

Publish the configuration file

```
php artisan vendor:publish --provider="Nuwave\Relay\LaravelServiceProvider"
```

Create a ```schema.php``` file and add the path to the config

```
// config/relay.php
// ...
'schema' => [
    'path'      => 'Http/schema.php',
    'output'    => null,
],
```

To generate a ```schema.json``` file (used with the Babel Relay Plugin):

```
php artisan relay:schema
```

*You can customize the output path in the ```relay.php``` config file under ```schema.output```*

For additional documentation, look through the docs folder or read the Wiki.
