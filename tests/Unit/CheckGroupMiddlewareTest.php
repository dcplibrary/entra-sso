<?php

namespace Dcplibrary\EntraSSO\Tests\Unit;

use Dcplibrary\EntraSSO\Http\Middleware\CheckGroup;
use Dcplibrary\EntraSSO\Models\User;
use Dcplibrary\EntraSSO\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckGroupMiddlewareTest extends TestCase
{
    /** @test */
    public function it_allows_user_in_correct_group()
    {
        $user = new User(['entra_groups' => ['IT Admins', 'Developers']]);
        $this->actingAs($user);

        $middleware = new CheckGroup();
        $request = Request::create('/servers', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'IT Admins');

        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_allows_user_in_one_of_multiple_groups()
    {
        $user = new User(['entra_groups' => ['Developers']]);
        $this->actingAs($user);

        $middleware = new CheckGroup();
        $request = Request::create('/servers', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'IT Admins', 'Developers');

        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_blocks_user_not_in_group()
    {
        $user = new User(['entra_groups' => ['HR']]);
        $this->actingAs($user);

        $middleware = new CheckGroup();
        $request = Request::create('/servers', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'IT Admins');

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_user_with_no_groups()
    {
        $user = new User(['entra_groups' => []]);
        $this->actingAs($user);

        $middleware = new CheckGroup();
        $request = Request::create('/servers', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'IT Admins');

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_unauthenticated_user()
    {
        $middleware = new CheckGroup();
        $request = Request::create('/servers', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'IT Admins');

        $this->assertEquals(403, $response->getStatusCode());
    }
}
