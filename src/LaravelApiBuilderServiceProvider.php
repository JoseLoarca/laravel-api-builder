<?php

namespace JoseLoarca\LaravelApiBuilder;

use Illuminate\Support\ServiceProvider;

class LaravelApiBuilderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('/lang'),
        ], 'lang');

    }

    public function register()
    {
    }
}
