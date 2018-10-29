<?php

namespace Kins\DirectumConnector;

use Exception;
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
        $this->publishes([
            __DIR__ . '/../config/directum-connector.php' => config_path('directum-connector.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../config/directum-connector.php', 'directum-connector');

        if (!class_exists('AddDirectumFieldsToUsersTable')) {
            $timestamp = date('Y_m_d_His');
            $this->publishes([
                __DIR__ . '/../migrations/add_directum_fields_to_users_table.php.stub' => database_path("/migrations/{$timestamp}_add_directum_fields_to_users_table.php"),
            ], 'migrations');
        }

        include __DIR__ . '/routes.php';
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('DirectumService', function ($app) {
            $config = $app->make('config');
            $uri = $config->get('directum-connector.uri');
            if (empty($uri)) {
                throw new Exception('Directum WSDL URI is empty');
            }
            return new DirectumService($uri);
        });
    }
}
