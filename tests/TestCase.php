<?php

namespace Dcplibrary\EntraSSO\Tests;

use Dcplibrary\EntraSSO\EntraSSOServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            EntraSSOServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup package config for testing
        $app['config']->set('entra-sso.tenant_id', 'test-tenant-id');
        $app['config']->set('entra-sso.client_id', 'test-client-id');
        $app['config']->set('entra-sso.client_secret', 'test-client-secret');
        $app['config']->set('entra-sso.redirect_uri', 'http://localhost/auth/entra/callback');
        $app['config']->set('entra-sso.auto_create_users', true);
        $app['config']->set('entra-sso.sync_groups', true);
        $app['config']->set('entra-sso.default_role', 'user');
    }
}
