<?php

namespace Dcplibrary\EntraSSO;

use Illuminate\Support\Facades\Http;
use Exception;

class EntraSSOService
{
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $baseUrl;

    public function __construct($tenantId, $clientId, $clientSecret, $redirectUri)
    {
        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->baseUrl = "https://login.microsoftonline.com/{$tenantId}";
    }

    public function getAuthorizationUrl($state = null)
    {
        $state = $state ?: bin2hex(random_bytes(16));
        session(['entra_sso_state' => $state]);

        $params = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read offline_access GroupMember.Read.All',
            'state' => $state,
        ]);

        return "{$this->baseUrl}/oauth2/v2.0/authorize?{$params}";
    }

    public function getAccessToken($code)
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth2/v2.0/token", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to obtain access token: ' . $response->body());
        }

        return $response->json();
    }

    public function getUserInfo($accessToken)
    {
        $response = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me');

        if (!$response->successful()) {
            throw new Exception('Failed to get user info: ' . $response->body());
        }

        return $response->json();
    }

    public function validateState($requestState)
    {
        $sessionState = session('entra_sso_state');
        session()->forget('entra_sso_state');
        
        return $sessionState && $requestState === $sessionState;
    }

    public function getUserGroups($accessToken)
    {
        $response = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me/memberOf');

        if (!$response->successful()) {
            throw new Exception('Failed to get user groups: ' . $response->body());
        }

        return $response->json()['value'] ?? [];
    }

    public function getUserRoles($accessToken)
    {
        $response = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me/appRoleAssignments');

        if (!$response->successful()) {
            throw new Exception('Failed to get user roles: ' . $response->body());
        }

        return $response->json()['value'] ?? [];
    }

    public function refreshAccessToken($refreshToken)
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth2/v2.0/token", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'scope' => 'openid profile email User.Read offline_access GroupMember.Read.All',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to refresh access token: ' . $response->body());
        }

        return $response->json();
    }

    public function parseIdToken($idToken)
    {
        $parts = explode('.', $idToken);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid ID token format');
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        
        return $payload;
    }

    public function getCustomClaims($idToken)
    {
        $claims = $this->parseIdToken($idToken);
        
        $standardClaims = [
            'iss', 'sub', 'aud', 'exp', 'iat', 'auth_time', 'nonce',
            'at_hash', 'c_hash', 'acr', 'amr', 'azp', 'email', 'email_verified',
            'family_name', 'given_name', 'name', 'preferred_username',
            'oid', 'tid', 'ver', 'rh', 'uti', 'aio', 'ipaddr', 'unique_name'
        ];
        
        return array_diff_key($claims, array_flip($standardClaims));
    }
}
