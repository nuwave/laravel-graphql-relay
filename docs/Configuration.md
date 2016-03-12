## Configuration ##

When publishing the configuration, the package will create a ```relay.php``` file in your ```config``` folder.

### Namespaces ###

```php
'namespaces' => [
    'mutations' => 'App\\GraphQL\\Mutations',
    'queries'   => 'App\\GraphQL\\Queries',
    'types'     => 'App\\GraphQL\\Types',
    'fields'    => 'App\\GraphQL\\Fields',
],
```

This package provides a list of commands that allows you to create Types, Mutations, Queries and Fields. You can specify the namespaces you would like the package to use when generating the files.

### Schema ###

```php
'schema' => [
    'file'      => 'Http/GraphQL/schema.php',
    'output'    => null,
]
```

** File **

Set the location of your schema file. (A schema is similar to your routes.php file and defines your Types, Mutations and Queries for GraphQL. Read More)

** Output **

This is the location where your generated ```schema.json``` will be created/updated. (This json file is used by the [Babel Relay Plugin](https://facebook.github.io/relay/docs/guides-babel-plugin.html#content)).

### Eloquent ###

```php
'eloquent' => [
    'path' => 'App\\Models',
    'camel_case' => false
]
```

** Path **

The package allows you to create Types based off of your Eloquent models. You can use the ```path``` to define the namespace of your models or you can use the full namespace when generating Types from the console (Read More).

** Camel Case **

Camel casing is quite common in javascript, but Laravel's database column naming convention is snake case. If you would like your Eloquent model's generated fields converted to camel case, you may set this to true.

*This works great with the [Eloquence package](https://github.com/kirkbushell/eloquence).*
