<?php

namespace Dcplibrary\EntraSSO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Dcplibrary\EntraSSO\EntraSSOService;

class EntraSSOController extends Controller
{
    protected $ssoService;

    public function __construct(EntraSSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function redirect()
    {
        return redirect($this->ssoService->getAuthorizationUrl());
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect('/login')->withErrors([
                'entra_sso' => 'Authentication failed: ' . $request->get('error_description', $request->get('error'))
            ]);
        }

        if (!$this->ssoService->validateState($request->get('state'))) {
            return redirect('/login')->withErrors([
                'entra_sso' => 'Invalid state parameter. Possible CSRF attack.'
            ]);
        }

        try {
            $tokenData = $this->ssoService->getAccessToken($request->get('code'));
            $userInfo = $this->ssoService->getUserInfo($tokenData['access_token']);
            
            $user = $this->findOrCreateUser($userInfo, $tokenData);
            
            if (config('entra-sso.sync_groups')) {
                $this->syncUserGroupsAndRoles($user, $tokenData['access_token']);
            }
            
            if (config('entra-sso.enable_token_refresh')) {
                session([
                    'entra_access_token' => $tokenData['access_token'],
                    'entra_refresh_token' => $tokenData['refresh_token'] ?? null,
                    'entra_token_expires_at' => now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600)),
                ]);
            }
            
            Auth::login($user);

            $redirectTo = config('entra-sso.redirect_after_login', '/entra/dashboard');
            return redirect()->intended($redirectTo);
            
        } catch (\Exception $e) {
            return redirect('/login')->withErrors([
                'entra_sso' => 'Authentication failed: ' . $e->getMessage()
            ]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }

    protected function findOrCreateUser($userInfo, $tokenData)
    {
        $userModel = config('entra-sso.user_model');
        $user = $userModel::where('email', $userInfo['mail'] ?? $userInfo['userPrincipalName'])->first();

        $userData = [
            'name' => $userInfo['displayName'],
            'email' => $userInfo['mail'] ?? $userInfo['userPrincipalName'],
            'entra_id' => $userInfo['id'],
        ];

        if (isset($tokenData['id_token'])) {
            $customClaims = $this->ssoService->getCustomClaims($tokenData['id_token']);
            $userData = array_merge($userData, $this->mapCustomClaims($customClaims));
        }

        if (!$user && config('entra-sso.auto_create_users')) {
            $userData['password'] = bcrypt(bin2hex(random_bytes(32)));
            $user = $userModel::create($userData);
        } elseif ($user) {
            $user->update($userData);
        }

        return $user;
    }

    protected function mapCustomClaims($customClaims)
    {
        $mapping = config('entra-sso.custom_claims_mapping', []);
        $mapped = [];

        foreach ($mapping as $claimName => $userAttribute) {
            if (isset($customClaims[$claimName])) {
                $mapped[$userAttribute] = $customClaims[$claimName];
            }
        }

        if (config('entra-sso.store_custom_claims')) {
            $mapped['entra_custom_claims'] = $customClaims;
        }

        return $mapped;
    }

    protected function syncUserGroupsAndRoles($user, $accessToken)
    {
        if (!$user || !config('entra-sso.sync_on_login') && $user->wasRecentlyCreated === false) {
            return;
        }

        try {
            $groups = $this->ssoService->getUserGroups($accessToken);
            
            $groupNames = array_column($groups, 'displayName');
            
            if (in_array('entra_groups', $user->getFillable()) || 
                method_exists($user, 'setEntraGroupsAttribute')) {
                $user->entra_groups = $groupNames;
            }
            
            $mappedRole = $this->mapGroupsToRole($groups);
            $this->assignRoleToUser($user, $mappedRole);
            
            $user->save();
            
        } catch (\Exception $e) {
            \Log::warning('Failed to sync groups and roles: ' . $e->getMessage());
        }
    }

    protected function mapGroupsToRole($groups)
    {
        $mapping = config('entra-sso.group_role_mapping', []);
        
        foreach ($groups as $group) {
            $groupName = $group['displayName'] ?? '';
            $groupId = $group['id'] ?? '';
            
            if (isset($mapping[$groupName])) {
                return $mapping[$groupName];
            }
            
            if (isset($mapping[$groupId])) {
                return $mapping[$groupId];
            }
        }
        
        return config('entra-sso.default_role', 'user');
    }

    protected function assignRoleToUser($user, $roleName)
    {
        $roleModel = config('entra-sso.role_model');
        
        if ($roleModel) {
            $role = $roleModel::where('name', $roleName)->first();
            
            if ($role && method_exists($user, 'syncRoles')) {
                $user->syncRoles([$role]);
            } elseif ($role && method_exists($user, 'roles')) {
                $user->roles()->sync([$role->id]);
            }
        } else {
            if (in_array('role', $user->getFillable()) || 
                method_exists($user, 'setRoleAttribute')) {
                $user->role = $roleName;
            }
        }
    }
}
