# laravel-graphql-relay

## Documentation currently under development

Use Facebook [GraphQL](http://facebook.github.io/graphql/) with [React Relay](https://facebook.github.io/relay/). This package is used alongside [laravel-graphql](https://github.com/Folkloreatelier/laravel-graphql) and is currently **a work in progress**. You can reference what specifications GraphQL needs to provide to work with Relay in the [documentation](https://facebook.github.io/relay/docs/graphql-relay-specification.html#content).

## Installation

You must then modify your composer.json file and run composer update to include the latest version of the package in your project.

```json
"require": {
    "nuwave/laravel-graphql-relay": "0.1.*"
}
```

Or you can use the ```composer require``` command from your terminal.

```json
composer require nuwave/laravel-graphql-relay
```

Add the service provider to your ```app/config.php``` file

```php
Nuwave\Relay\ServiceProvider::class
```

Add the following entries to your ```config/graphql.php``` file ([laravel-graphql configuration file](https://github.com/Folkloreatelier/laravel-graphql#installation-1))

```php
'schema' => [
    'query' => [
        // ...
        'node' => Nuwave\Relay\Node\NodeQuery::class,
    ],
    // ...
],
'types' => [
    // ...
    'node' => Nuwave\Relay\Node\NodeType::class,
    'pageInfo' => Nuwave\Relay\Types\PageInfoType::class,
]
```

To generate a ```schema.json``` file (used with the [Babel Relay Plugin](https://facebook.github.io/relay/docs/guides-babel-plugin.html#content))

```php
php artisan relay:schema
```

For additional documentation, please read the [Wiki](https://github.com/nuwave/laravel-graphql-relay/wiki/1.-GraphQL-and-Relay)
