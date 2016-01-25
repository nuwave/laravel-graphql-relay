<?php
/*
|--------------------------------------------------------------------------
| Schema File
|--------------------------------------------------------------------------
|
| You can utilize this file to register all of you GraphQL schma queries
| and mutations. You can group collections together by namespace or middlware.
|
*/

return [
    // The path where you published your graphql.php file
    // https://github.com/Folkloreatelier/laravel-graphql#installation-1
    'graphql_config' => config_path('graphql.php'),

    // Add your mutations and queries here.
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
