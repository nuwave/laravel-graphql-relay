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
        $this->publishes([__DIR__ . '../config/config.php' => config_path('relay.php')]);

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
            \Nuwave\Relay\Commands\SchemaCommand::class,
        ]);

        $this->app->singleton('relay', function ($app) {
            return new SchemaContainer($app);
        });

        $this->app->alias('relay', SchemaContainer::class);

        $this->mergeConfigFrom(__DIR__ . '../config/config.php', 'relay');
    }

    /**
     * Register schema mutations and queries.
     *
     * @return void
     */
    protected function registerSchema()
    {
        $namespace = config('relay.namespace');
        $register = config('relay.schema');

        $register($namespace);
    }
}
