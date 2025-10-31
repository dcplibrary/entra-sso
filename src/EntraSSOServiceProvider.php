<?php

namespace Dcplibrary\EntraSSO;

use Illuminate\Support\ServiceProvider;

class EntraSSOServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/entra-sso.php', 'entra-sso'
        );

        $this->app->singleton(EntraSSOService::class, function ($app) {
            return new EntraSSOService(
                config('entra-sso.tenant_id'),
                config('entra-sso.client_id'),
                config('entra-sso.client_secret'),
                config('entra-sso.redirect_uri')
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/entra-sso.php' => config_path('entra-sso.php'),
        ], 'entra-sso-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'entra-sso');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadConfigFrom(__DIR__.'/../config/entra-sso.php', 'entra-sso');
        
        // Register middleware
        app('router')->aliasMiddleware('entra.role', \Dcplibrary\EntraSSO\Http\Middleware\CheckRole::class);
        app('router')->aliasMiddleware('entra.group', \Dcplibrary\EntraSSO\Http\Middleware\CheckGroup::class);
        app('router')->aliasMiddleware('entra.refresh', \Dcplibrary\EntraSSO\Http\Middleware\RefreshEntraToken::class);
        
        // Add refresh middleware to web group if enabled
        if (config('entra-sso.enable_token_refresh')) {
            app('router')->pushMiddlewareToGroup('web', \Dcplibrary\EntraSSO\Http\Middleware\RefreshEntraToken::class);
        }
    }

    protected function loadConfigFrom($path, $key)
    {
        // Laravel doesn't provide loadConfigFrom, alias to mergeConfigFrom
        $this->mergeConfigFrom($path, $key);
    }
}
