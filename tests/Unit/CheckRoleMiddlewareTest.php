<?php

namespace Dcplibrary\EntraSSO\Tests\Unit;

use Dcplibrary\EntraSSO\Http\Middleware\CheckRole;
use Dcplibrary\EntraSSO\Models\User;
use Dcplibrary\EntraSSO\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckRoleMiddlewareTest extends TestCase
{
    /** @test */
    public function it_allows_user_with_correct_role()
    {
        $user = new User(['role' => 'admin']);
        $this->actingAs($user);

        $middleware = new CheckRole();
        $request = Request::create('/admin', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin');

        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_allows_user_with_one_of_multiple_roles()
    {
        $user = new User(['role' => 'manager']);
        $this->actingAs($user);

        $middleware = new CheckRole();
        $request = Request::create('/admin', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin', 'manager');

        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_blocks_user_with_wrong_role()
    {
        $user = new User(['role' => 'user']);
        $this->actingAs($user);

        $middleware = new CheckRole();
        $request = Request::create('/admin', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_unauthenticated_user()
    {
        $middleware = new CheckRole();
        $request = Request::create('/admin', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
    }
}
