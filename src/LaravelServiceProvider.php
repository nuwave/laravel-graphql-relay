<?php

namespace Nuwave\Relay;

use Nuwave\Relay\Schema\GraphQL;
use Nuwave\Relay\Commands\FieldMakeCommand;
use Nuwave\Relay\Commands\MutationMakeCommand;
use Nuwave\Relay\Commands\QueryMakeCommand;
use Nuwave\Relay\Commands\SchemaCommand;
use Nuwave\Relay\Commands\TypeMakeCommand;
use Nuwave\Relay\Schema\Parser;
use Nuwave\Relay\Schema\SchemaContainer;
use Illuminate\Support\ServiceProvider as BaseProvider;

class LaravelServiceProvider extends BaseProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/config.php' => config_path('relay.php')]);

        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'relay');

        $this->registerSchema();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            SchemaCommand::class,
            MutationMakeCommand::class,
            FieldMakeCommand::class,
            QueryMakeCommand::class,
            TypeMakeCommand::class,
        ]);

        $this->app->singleton('graphql', function ($app) {
            return new GraphQL($app);
        });

        $this->app->singleton('relay', function ($app) {
            return new SchemaContainer(new Parser);
        });
    }

    /**
     * Register schema mutations and queries.
     *
     * @return void
     */
    protected function registerSchema()
    {
        require_once app_path(config('relay.schema_path'));

        $this->setGraphQLConfig();

        $this->registerRelayTypes();

        $this->initializeTypes();
    }

    /**
     * Register the default relay types in the schema.
     *
     * @return void
     */
    protected function registerRelayTypes()
    {
        $relay = $this->app['relay'];

        $relay->group(['namespace' => 'Nuwave\\Relay'], function () use ($relay) {
            $relay->query('node', 'Node\\NodeQuery');
            $relay->type('node', 'Node\\NodeType');
            $relay->type('pageInfo', 'Types\\PageInfoType');
        });
    }

    /**
     * Set GraphQL configuration variables.
     *
     * @return void
     */
    protected function setGraphQLConfig()
    {
        $relay = $this->app['relay'];

        $mutations = config('graphql.schema.mutation', []);
        $queries = config('graphql.schema.query', []);
        $types = config('graphql.types', []);

        config([
            'graphql.schema.mutation' => array_merge($mutations, $relay->getMutations()->config()),
            'graphql.schema.query' => array_merge($queries, $relay->getQueries()->config()),
            'graphql.types' => array_merge($types, $relay->getTypes()->config())
        ]);
    }

    /**
     * Initialize GraphQL types array.
     *
     * @return void
     */
    protected function initializeTypes()
    {
        foreach (config('graphql.types') as $name => $type) {
            $this->app['graphql']->addType($type, $name);
        }
    }
}
