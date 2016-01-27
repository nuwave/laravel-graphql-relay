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

Add the Relay facade to your ```app/config.php``` file

```php
'Relay' => Nuwave\Relay\Facades\Relay::class,
```

Publish the configuration file

```php
php artisan vendor:publish --provider="Nuwave\Relay\ServiceProvider"
```

Add your Mutations, Queries and Types to the ```config/relay.php``` file

```php
// Example:

return [
    'schema' => function () {
        // Added by default
        Relay::group(['namespace' => 'Nuwave\\Relay'], function () {
            Relay::group(['namespace' => 'Node'], function () {
                Relay::query('node', 'NodeQuery');
                Relay::type('node', 'NodeType');
            });

            Relay::type('pageInfo', 'Types\\PageInfoType');
        });

        // Your mutations, queries and types
        Relay::group(['namespace' => 'App\\Http\\GraphQL'], function () {
            Relay::group(['namespace' => 'Mutations'], function () {
                Relay::mutation('createUser', 'CreateUserMutation');
            });

            Relay::group(['namespace' => 'Queries', function () {
                Relay::query('userQuery', 'UserQuery');
            });

            Relay::group(['namespace' => 'Types'], function () {
                Relay::type('user', 'UserType');
                Relay::type('event', 'EventType');
            });
        });
    }
];
```

To generate a ```schema.json``` file (used with the [Babel Relay Plugin](https://facebook.github.io/relay/docs/guides-babel-plugin.html#content))

```php
php artisan relay:schema
```

For additional documentation, please read the [Wiki](https://github.com/nuwave/laravel-graphql-relay/wiki/1.-GraphQL-and-Relay)
