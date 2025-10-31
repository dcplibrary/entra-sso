<?php

namespace Dcplibrary\EntraSSO\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect('/login');
        }

        $userRole = $this->getUserRole($request->user());

        if (!in_array($userRole, $roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }

    protected function getUserRole($user)
    {
        if (isset($user->role)) {
            return $user->role;
        }

        if (method_exists($user, 'roles')) {
            $roles = $user->roles;
            return $roles->isNotEmpty() ? $roles->first()->name : 'user';
        }

        if (method_exists($user, 'getRoleNames')) {
            $roleNames = $user->getRoleNames();
            return $roleNames->isNotEmpty() ? $roleNames->first() : 'user';
        }

        return 'user';
    }
}
