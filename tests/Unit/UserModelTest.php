<?php

namespace Dcplibrary\EntraSSO\Tests\Unit;

use Dcplibrary\EntraSSO\Models\User;
use Dcplibrary\EntraSSO\Tests\TestCase;

class UserModelTest extends TestCase
{
    /** @test */
    public function it_can_check_if_user_has_role()
    {
        $user = new User(['role' => 'admin']);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
    }

    /** @test */
    public function it_can_check_if_user_has_any_role()
    {
        $user = new User(['role' => 'admin']);

        $this->assertTrue($user->hasAnyRole(['admin', 'manager']));
        $this->assertFalse($user->hasAnyRole(['user', 'guest']));
    }

    /** @test */
    public function it_can_check_if_user_is_admin()
    {
        $adminUser = new User(['role' => 'admin']);
        $regularUser = new User(['role' => 'user']);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($regularUser->isAdmin());
    }

    /** @test */
    public function it_can_check_if_user_is_manager()
    {
        $managerUser = new User(['role' => 'manager']);
        $regularUser = new User(['role' => 'user']);

        $this->assertTrue($managerUser->isManager());
        $this->assertFalse($regularUser->isManager());
    }

    /** @test */
    public function it_can_check_if_user_is_in_group()
    {
        $user = new User([
            'entra_groups' => ['IT Admins', 'Developers']
        ]);

        $this->assertTrue($user->inGroup('IT Admins'));
        $this->assertTrue($user->inGroup('Developers'));
        $this->assertFalse($user->inGroup('HR'));
    }

    /** @test */
    public function it_can_check_if_user_is_in_any_group()
    {
        $user = new User([
            'entra_groups' => ['IT Admins', 'Developers']
        ]);

        $this->assertTrue($user->inAnyGroup(['IT Admins', 'HR']));
        $this->assertTrue($user->inAnyGroup(['Developers']));
        $this->assertFalse($user->inAnyGroup(['HR', 'Marketing']));
    }

    /** @test */
    public function it_can_get_custom_claim()
    {
        $user = new User([
            'entra_custom_claims' => [
                'department' => 'Engineering',
                'jobTitle' => 'Senior Developer'
            ]
        ]);

        $this->assertEquals('Engineering', $user->getCustomClaim('department'));
        $this->assertEquals('Senior Developer', $user->getCustomClaim('jobTitle'));
        $this->assertNull($user->getCustomClaim('nonexistent'));
        $this->assertEquals('default', $user->getCustomClaim('nonexistent', 'default'));
    }

    /** @test */
    public function it_can_check_if_user_has_custom_claim()
    {
        $user = new User([
            'entra_custom_claims' => [
                'department' => 'Engineering'
            ]
        ]);

        $this->assertTrue($user->hasCustomClaim('department'));
        $this->assertFalse($user->hasCustomClaim('jobTitle'));
    }

    /** @test */
    public function it_can_get_all_entra_groups()
    {
        $user = new User([
            'entra_groups' => ['IT Admins', 'Developers']
        ]);

        $this->assertEquals(['IT Admins', 'Developers'], $user->getEntraGroups());
    }

    /** @test */
    public function it_returns_empty_array_when_no_groups()
    {
        $user = new User();

        $this->assertEquals([], $user->getEntraGroups());
    }

    /** @test */
    public function it_can_get_all_custom_claims()
    {
        $claims = [
            'department' => 'Engineering',
            'jobTitle' => 'Senior Developer'
        ];

        $user = new User(['entra_custom_claims' => $claims]);

        $this->assertEquals($claims, $user->getCustomClaims());
    }

    /** @test */
    public function it_returns_empty_array_when_no_custom_claims()
    {
        $user = new User();

        $this->assertEquals([], $user->getCustomClaims());
    }

    /** @test */
    public function it_casts_entra_groups_as_array()
    {
        $user = new User();

        $casts = $user->casts();

        $this->assertArrayHasKey('entra_groups', $casts);
        $this->assertEquals('array', $casts['entra_groups']);
    }

    /** @test */
    public function it_casts_entra_custom_claims_as_array()
    {
        $user = new User();

        $casts = $user->casts();

        $this->assertArrayHasKey('entra_custom_claims', $casts);
        $this->assertEquals('array', $casts['entra_custom_claims']);
    }

    /** @test */
    public function it_includes_entra_fields_in_fillable()
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('entra_id', $fillable);
        $this->assertContains('role', $fillable);
        $this->assertContains('entra_groups', $fillable);
        $this->assertContains('entra_custom_claims', $fillable);
    }
}
