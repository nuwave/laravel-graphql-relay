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
    // Default namespace of your GraphQL queries and mutations.
    'namespace' => 'App\\Http\\GraphQL',

    // The path where you published your graphql.php file
    // https://github.com/Folkloreatelier/laravel-graphql#installation-1
    'graphql_config' => config_path('graphql.php'),

    // Add your mutations and queries here.
    'schema' => function ($namespace) {
        Relay::group(['namespace' => $namespace], function () {
            // ...
        });
    }
];
