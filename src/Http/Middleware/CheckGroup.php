<?php

namespace Dcplibrary\EntraSSO\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGroup
{
    public function handle(Request $request, Closure $next, string ...$groups): Response
    {
        if (!$request->user()) {
            return redirect('/login');
        }

        $userGroups = $request->user()->entra_groups ?? [];

        if (empty(array_intersect($groups, $userGroups))) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
