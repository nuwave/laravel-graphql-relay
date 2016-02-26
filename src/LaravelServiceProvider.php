<?php

namespace Nuwave\Relay;

use Folklore\GraphQL\GraphQL;
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

        $this->initializeTypes();
    }

    /**
     * Set GraphQL configuration variables.
     *
     * @return void
     */
    protected function setGraphQLConfig()
    {
        $relay = $this->app['relay'];

        config([
            'graphql.schema.mutation' => $relay->getMutations()->config(),
            'graphql.schema.query' => $relay->getQueries()->config(),
            'graphql.types' => $relay->getTypes()->config(),
        ]);
    }

    /**
     * Initialize GraphQL types array.
     *
     * @return void
     */
    protected function initializeTypes()
    {
        foreach(config('graphql.types') as $name => $type) {
            $this->app['graphql']->addType($type, $name);
        }
    }
}
