<?php

namespace JoseLoarca\LaravelApiBuilder;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use JoseLoarca\LaravelApiBuilder\Middleware\HandleCors;
use JoseLoarca\LaravelApiBuilder\Middleware\RequestsLogger;

class LaravelApiBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/requests-logger.php', 'requests-logger');

        $this->commands(
            [Commands\BuildApiCommand::class]
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            //Publishes ES translations files
            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('/lang'),
            ], 'lang');

            //Publish Requests Logger configuration file
            $this->publishes([
                __DIR__.'/../config/requests-logger.php' => config_path('requests-logger.php'),
            ], 'config');
        }

        $this->registerMiddlewareToGroup(HandleCors::class, 'api');

        $this->registerMiddleware(RequestsLogger::class);
    }

    /**
     * Add a new middleware to end of the stack if it does not already exist.
     *
     * @param $middleware
     *
     * @return void
     */
    protected function registerMiddleware($middleware)
    {
        $kernel = $this->app[Kernel::class];

        $kernel->pushMiddleware($middleware);
    }

    /**
     * Add a new middleware to end of the stack if it does not already exist.
     *
     * @param $middleware
     * @param string $group
     *
     * @return void
     */
    protected function registerMiddlewareToGroup($middleware, string $group)
    {
        $kernel = $this->app['router'];

        $kernel->pushMiddlewareToGroup($group, $middleware);
    }
}
