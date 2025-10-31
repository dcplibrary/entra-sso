<?php

namespace Dcplibrary\EntraSSO\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Dcplibrary\EntraSSO\EntraSSOService;

class RefreshEntraToken
{
    protected $ssoService;

    public function __construct(EntraSSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return $next($request);
        }

        if (!config('entra-sso.enable_token_refresh')) {
            return $next($request);
        }

        $expiresAt = session('entra_token_expires_at');
        $refreshToken = session('entra_refresh_token');

        if (!$expiresAt || !$refreshToken) {
            return $next($request);
        }

        $thresholdMinutes = (int) config('entra-sso.refresh_threshold_minutes', 5);
        $shouldRefresh = now()->addMinutes($thresholdMinutes)->greaterThan($expiresAt);

        if ($shouldRefresh) {
            try {
                $tokenData = $this->ssoService->refreshAccessToken($refreshToken);

                session([
                    'entra_access_token' => $tokenData['access_token'],
                    'entra_refresh_token' => $tokenData['refresh_token'] ?? $refreshToken,
                    'entra_token_expires_at' => now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600)),
                ]);

                \Log::info('Entra token refreshed for user: ' . $request->user()->id);

            } catch (\Exception $e) {
                \Log::warning('Failed to refresh Entra token: ' . $e->getMessage());
                
                if (config('entra-sso.logout_on_refresh_failure', false)) {
                    auth()->logout();
                    return redirect()->route('login')->withErrors([
                        'session_expired' => 'Your session has expired. Please log in again.'
                    ]);
                }
            }
        }

        return $next($request);
    }
}
