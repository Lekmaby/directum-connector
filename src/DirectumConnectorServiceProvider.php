<?php

namespace Kins\DirectumConnector;

use Illuminate\Support\ServiceProvider;

class DirectumConnectorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/routes.php';

        $this->publishes([
            __DIR__ . '/../config/directum-connector.php' => config_path('directum-connector.php'),
        ], 'directum-connector');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/directum-connector.php', 'directum-connector');

        $this->app->singleton('directum-connector', function ($app) {
            $config = $app->make('config');
            $uri = $config->get('directum-connector.uri');
            return new DirectumService($uri);
        });
    }

    public function provides()
    {
        return ['directum-connector'];
    }
}
