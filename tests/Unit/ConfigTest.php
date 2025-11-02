<?php

namespace Dcplibrary\EntraSSO\Tests\Unit;

use Dcplibrary\EntraSSO\Tests\TestCase;

class ConfigTest extends TestCase
{
    /** @test */
    public function it_parses_group_role_mapping_from_env()
    {
        config(['entra-sso' => null]); // Reset config

        putenv('ENTRA_GROUP_ROLES=IT Admins:admin,Developers:developer,Staff:user');

        $this->app['config']->set('entra-sso.group_role_mapping',
            collect(explode(',', env('ENTRA_GROUP_ROLES')))
                ->mapWithKeys(function ($mapping) {
                    $parts = explode(':', trim($mapping), 2);
                    return count($parts) === 2 ? [trim($parts[0]) => trim($parts[1])] : [];
                })
                ->filter()
                ->toArray()
        );

        $mapping = config('entra-sso.group_role_mapping');

        $this->assertIsArray($mapping);
        $this->assertEquals('admin', $mapping['IT Admins']);
        $this->assertEquals('developer', $mapping['Developers']);
        $this->assertEquals('user', $mapping['Staff']);

        putenv('ENTRA_GROUP_ROLES'); // Clean up
    }

    /** @test */
    public function it_handles_empty_group_role_mapping()
    {
        putenv('ENTRA_GROUP_ROLES=');

        $mapping = env('ENTRA_GROUP_ROLES')
            ? collect(explode(',', env('ENTRA_GROUP_ROLES')))
                ->mapWithKeys(function ($mapping) {
                    $parts = explode(':', trim($mapping), 2);
                    return count($parts) === 2 ? [trim($parts[0]) => trim($parts[1])] : [];
                })
                ->filter()
                ->toArray()
            : [];

        $this->assertIsArray($mapping);
        $this->assertEmpty($mapping);

        putenv('ENTRA_GROUP_ROLES'); // Clean up
    }

    /** @test */
    public function it_handles_group_names_with_spaces()
    {
        putenv('ENTRA_GROUP_ROLES=Computer Services Team:admin,Web Developers:developer');

        $this->app['config']->set('entra-sso.group_role_mapping',
            collect(explode(',', env('ENTRA_GROUP_ROLES')))
                ->mapWithKeys(function ($mapping) {
                    $parts = explode(':', trim($mapping), 2);
                    return count($parts) === 2 ? [trim($parts[0]) => trim($parts[1])] : [];
                })
                ->filter()
                ->toArray()
        );

        $mapping = config('entra-sso.group_role_mapping');

        $this->assertEquals('admin', $mapping['Computer Services Team']);
        $this->assertEquals('developer', $mapping['Web Developers']);

        putenv('ENTRA_GROUP_ROLES'); // Clean up
    }

    /** @test */
    public function it_loads_default_config_values()
    {
        $this->assertEquals('user', config('entra-sso.default_role'));
        $this->assertTrue(config('entra-sso.auto_create_users'));
        $this->assertTrue(config('entra-sso.sync_groups'));
    }
}
