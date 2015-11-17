<?php

namespace Nuwave\Relay\Tests;

abstract class BaseTest extends \Orchestra\Testbench\TestCase
{
    /**
     * Generate GraphQL Response.
     *
     * @param  string  $query
     * @param  array   $variables
     * @param  boolean $encode
     * @return array|string
     */
    protected function graphqlResponse($query, $variables = [], $encode = false)
    {
        $response = $this->app['graphql']->query($query, $variables);

        if ($encode) {
            return json_encode($response);
        }

        return $response;
    }

    /**
     * Get default service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Folklore\GraphQL\GraphQLServiceProvider::class,
            \Nuwave\Relay\ServiceProvider::class,
        ];
    }

    /**
     * Get list of package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'GraphQL' => \Folklore\GraphQL\Support\Facades\GraphQL::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('graphql', [
            'prefix' => 'graphql',
            'routes' => '/',
            'controllers' => '\Folklore\GraphQL\GraphQLController@query',
            'middleware' => [],
            'schema' => [
                'query' => [
                    'node' => \Nuwave\Relay\Node\NodeQuery::class,
                    'humanByName' => \Nuwave\Relay\Tests\Assets\Queries\HumanByName::class,
                ],
                'mutation' => [

                ]
            ],
            'types' => [
                'node' => \Nuwave\Relay\Node\NodeType::class,
                'pageInfo' => \Nuwave\Relay\Types\PageInfoType::class,
                'human' => \Nuwave\Relay\Tests\Assets\Types\HumanType::class,
            ]
        ]);
    }
}
