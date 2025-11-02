<?php

namespace Dcplibrary\EntraSSO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get role and group information
        $role = $this->getUserRole($user);
        $groups = $user->entra_groups ?? [];
        $customClaims = $user->entra_custom_claims ?? [];

        // Get group role mappings from config
        $groupRoleMappings = config('entra-sso.group_role_mapping', []);

        // Get available routes
        $routes = $this->getEntraSsoRoutes();

        return view('entra-sso::dashboard', compact(
            'user',
            'role',
            'groups',
            'customClaims',
            'groupRoleMappings',
            'routes'
        ));
    }

    protected function getUserRole($user)
    {
        // Check if user has role attribute
        if (isset($user->role)) {
            return $user->role;
        }

        // Check if user has relationship-based roles
        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames();
            return $roles->first() ?? 'No role assigned';
        }

        if (method_exists($user, 'roles')) {
            $roles = $user->roles->pluck('name');
            return $roles->first() ?? 'No role assigned';
        }

        return 'No role assigned';
    }

    protected function getEntraSsoRoutes()
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->getName() ?? '', 'entra.');
        });

        return $routes->map(function ($route) {
            return [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'methods' => $route->methods(),
            ];
        })->values();
    }
}
