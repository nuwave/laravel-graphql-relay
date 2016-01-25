<?php

namespace Nuwave\Relay;

use Nuwave\Relay\Schema\SchemaContainer;
use Illuminate\Support\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
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
        $this->setConfig();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            \Nuwave\Relay\Commands\SchemaCommand::class,
        ]);

        $this->app->singleton('relay', function ($app) {
            return new SchemaContainer;
        });

        $this->app->alias('relay', SchemaContainer::class);
    }

    /**
     * Register schema mutations and queries.
     *
     * @return void
     */
    protected function registerSchema()
    {
        $register = config('relay.schema');

        $register();
    }

    /**
     * Set configuration variables.
     *
     * @return void
     */
    protected function setConfig()
    {
        $schema = $this->app['relay'];

        $mutations = config('graphql.schema.mutation', []);
        $queries = config('graphql.schema.query', []);
        $types = config('graphql.types', []);

        config([
            'graphql.schema.mutation' => array_merge($mutations, $schema->getMutations()->config()),
            'graphql.schema.query' => array_merge($queries, $schema->getQueries()->config()),
            'graphql.types' => array_merge($types, $schema->getTypes()->config())
        ]);
    }
}
