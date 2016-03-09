<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Namespace registry
    |--------------------------------------------------------------------------
    |
    | This package provides a set of commands to make it easy for you to
    | create new parts in your GraphQL schema. Change these values to
    | match the namespaces you'd like each piece to be created in.
    |
    */

    'namespaces' => [
        'mutations' => 'App\\GraphQL\\Mutations',
        'queries'   => 'App\\GraphQL\\Queries',
        'types'     => 'App\\GraphQL\\Types',
        'fields'    => 'App\\GraphQL\\Fields',
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema declaration
    |--------------------------------------------------------------------------
    |
    | This is a path that points to where your Relay schema is located
    | relative to the app path. You should define your entire Relay
    | schema in this file. Declare any Relay queries, mutations,
    | and types here instead of laravel-graphql config file.
    |
    */

    'schema' => [
        'path'      => null,
        'output'    => null,
        'types'     => [],
        'mutations' => [],
        'queries'   => []
    ],

    'model_path' => 'App\\Models',
    'camel_case' => false,
];
