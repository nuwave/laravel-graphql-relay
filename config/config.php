<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Namespace registry
    |--------------------------------------------------------------------------
    |
    | Namespaces used when generating mutations, queries, types or fields with
    | the 'php artisan make:relay:{type}' command.
    |
    */
    'namespaces' => [
        'mutations' => 'App\\GraphQL\\Mutations',
        'queries'   => 'App\\GraphQL\\Queries',
        'types'     => 'App\\GraphQL\\Types',
        'fields'    => 'App\\GraphQL\\Fields'
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema declaration
    |--------------------------------------------------------------------------
    |
    | You can utilize this file to register all of you GraphQL schma queries
    | and mutations. You can group collections together by namespace or middlware.
    |
    */
    'schema' => function () {
        Relay::group(['namespace' => 'Nuwave\\Relay'], function () {
            Relay::group(['namespace' => 'Node'], function () {
                Relay::query('node', 'NodeQuery');
                Relay::type('node', 'NodeType');
            });

            Relay::type('pageInfo', 'Types\\PageInfoType');
        });

        // Additional Queries, Mutations and Types...
    }
];
