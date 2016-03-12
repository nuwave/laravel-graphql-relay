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
            \Nuwave\Relay\LaravelServiceProvider::class,
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
            'GraphQL' => \Nuwave\Relay\Facades\GraphQL::class,
            'Relay'   => \Nuwave\Relay\Facades\Relay::class,
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
        $app['config']->set('relay', [
            'schema' => [
                'path' => 'schema/schema.php',
                'queries' => [
                    'node' => \Nuwave\Relay\Node\NodeQuery::class,
                    'humanByName' => \Nuwave\Relay\Tests\Assets\Queries\HumanByName::class,
                ],
                'mutations' => [],
                'types' => [
                    'node' => \Nuwave\Relay\Node\NodeType::class,
                    'pageInfo' => \Nuwave\Relay\Support\Definition\PageInfoType::class,
                    'human' => \Nuwave\Relay\Tests\Assets\Types\HumanType::class,
                ],
            ]
        ]);
    }
}
