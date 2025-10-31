# Automatic Token Refresh

This package includes automatic token refresh functionality to maintain long-running user sessions without requiring re-authentication. Access tokens from Microsoft Entra typically expire after 1 hour, but with refresh tokens, you can seamlessly renew them.

## How It Works

1. During login, the package requests the `offline_access` scope to receive a refresh token
2. Access token, refresh token, and expiration time are stored in the user's session
3. On each request, the `RefreshEntraToken` middleware checks if the token is near expiration
4. If within the refresh threshold, the middleware automatically refreshes the token
5. The new access token and updated expiration time are stored in the session

## Configuration

### Enable Token Refresh

Edit your `config/entra-sso.php`:

```php
return [
    // Enable automatic token refresh
    'enable_token_refresh' => env('ENTRA_ENABLE_TOKEN_REFRESH', true),

    // Refresh when this many minutes remain before expiry (default: 5)
    'refresh_threshold_minutes' => (int) env('ENTRA_REFRESH_THRESHOLD', 5),

    // Optional: logout user if refresh fails
    'logout_on_refresh_failure' => env('ENTRA_LOGOUT_ON_REFRESH_FAILURE', false),
];
```

### Environment Variables

Add to your `.env` file:

```env
# Enable token refresh
ENTRA_ENABLE_TOKEN_REFRESH=true

# Refresh tokens when 5 minutes remain
ENTRA_REFRESH_THRESHOLD=5

# Optional: Force logout on refresh failure
ENTRA_LOGOUT_ON_REFRESH_FAILURE=false
```

## Middleware Registration

The middleware is automatically registered when `enable_token_refresh` is `true`. It's added to the `web` middleware group, so it runs on all web routes for authenticated users.

### Manual Middleware Usage

If you want more control, you can disable automatic registration and add the middleware manually:

```php
// In your route file
Route::middleware(['auth', 'entra.refresh'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

## Session Storage

Tokens are stored in the Laravel session (not in the database):

| Session Key | Description |
|-------------|-------------|
| `entra_access_token` | Current access token for Microsoft Graph API |
| `entra_refresh_token` | Refresh token (persists across token renewals) |
| `entra_token_expires_at` | Carbon timestamp of when the access token expires |

## Refresh Threshold

The `refresh_threshold_minutes` setting determines when to refresh:

```php
// With threshold of 5 minutes:
// - Token expires at: 2:00 PM
// - Refresh starts at: 1:55 PM (5 minutes before)
```

**Recommendations:**

- **Short sessions (1-2 hours):** 5 minutes threshold (default)
- **Long sessions (full day):** 10-15 minutes threshold
- **API-heavy applications:** 10 minutes threshold (reduces refresh frequency)

## Accessing Tokens in Your Application

You can access the stored tokens from anywhere in your application:

```php
// Get the current access token
$accessToken = session('entra_access_token');

// Get token expiration time
$expiresAt = session('entra_token_expires_at');

// Check if token is still valid
if ($expiresAt && now()->lessThan($expiresAt)) {
    // Token is still valid
}

// Get refresh token
$refreshToken = session('entra_refresh_token');
```

## Making Graph API Calls with Tokens

Use the stored access token to make custom Graph API calls:

```php
use Illuminate\Support\Facades\Http;

$accessToken = session('entra_access_token');

// Get user's calendar events
$response = Http::withToken($accessToken)
    ->get('https://graph.microsoft.com/v1.0/me/events');

$events = $response->json();
```

## Manual Token Refresh

You can manually trigger a token refresh using the `EntraSSOService`:

```php
use Dcplibrary\EntraSSO\EntraSSOService;

public function refreshMyToken(EntraSSOService $ssoService)
{
    $refreshToken = session('entra_refresh_token');

    try {
        $tokenData = $ssoService->refreshAccessToken($refreshToken);

        session([
            'entra_access_token' => $tokenData['access_token'],
            'entra_refresh_token' => $tokenData['refresh_token'] ?? $refreshToken,
            'entra_token_expires_at' => now()->addSeconds($tokenData['expires_in']),
        ]);

        return 'Token refreshed successfully';
    } catch (\Exception $e) {
        return 'Failed to refresh token: ' . $e->getMessage();
    }
}
```

## Handling Refresh Failures

### Log Warnings (Default)

By default, if token refresh fails, a warning is logged but the user remains logged in:

```php
'logout_on_refresh_failure' => false,
```

The user can continue using the application, but Graph API calls may fail until they log in again.

### Force Logout on Failure

For stricter security, you can force logout when refresh fails:

```php
'logout_on_refresh_failure' => true,
```

The user will be redirected to the login page with an error message.

## Security Considerations

### Token Storage

- Tokens are stored in Laravel sessions (server-side)
- Session data is encrypted by Laravel
- Tokens are NOT stored in cookies or local storage
- Tokens are NOT persisted to the database

### Token Lifetime

- Access tokens: ~1 hour (set by Microsoft)
- Refresh tokens: ~90 days (can be revoked by admin)
- Session lifetime: Set in `config/session.php`

### Revocation

Refresh tokens can be revoked:

1. **User changes password:** All refresh tokens are invalidated
2. **Admin revokes access:** Via Azure Portal → Entra ID → Users → Revoke sessions
3. **Session expires:** Refresh token is lost when session ends

## Monitoring Token Refresh

The middleware logs successful refreshes and failures:

```php
// Successful refresh
\Log::info('Entra token refreshed for user: ' . $userId);

// Failed refresh
\Log::warning('Failed to refresh Entra token: ' . $errorMessage);
```

Check your Laravel logs for these messages:

```bash
tail -f storage/logs/laravel.log | grep "Entra token"
```

## Disabling Token Refresh

To disable automatic token refresh:

```env
ENTRA_ENABLE_TOKEN_REFRESH=false
```

When disabled:

- Refresh tokens are not requested during login
- The middleware does not run
- Users must re-authenticate when their access token expires (after ~1 hour)

## Long-Running Sessions

For applications requiring all-day sessions:

1. **Enable token refresh:** `ENTRA_ENABLE_TOKEN_REFRESH=true`
2. **Set longer session lifetime:** Edit `config/session.php`
   ```php
   'lifetime' => 480, // 8 hours
   ```
3. **Use database sessions:** For better reliability
   ```env
   SESSION_DRIVER=database
   ```
4. **Monitor refresh failures:** Check logs regularly

## Troubleshooting

### Token not refreshing

1. Check that `enable_token_refresh` is `true`
2. Verify the middleware is running (check route list)
3. Ensure user is authenticated
4. Check that refresh token exists in session

### "Refresh token expired" errors

- Refresh tokens expire after ~90 days of inactivity
- User must log in again to get a new refresh token
- Cannot be prevented; this is a Microsoft limitation

### "Invalid grant" errors

Causes:
- Refresh token has been revoked
- User changed their password
- Admin revoked the user's sessions
- App permissions changed in Azure

Solution: User must log in again

### Tokens missing from session

1. Check session driver is working (`SESSION_DRIVER` in `.env`)
2. Verify session is not expiring too quickly
3. Check that login completed successfully
4. Ensure `offline_access` scope is being requested (see `src/EntraSSOService.php`)

## Performance Considerations

- Token refresh adds minimal overhead (~100-300ms per refresh)
- Refreshes only occur when threshold is reached (not on every request)
- Only one refresh happens per session, even with concurrent requests
- Consider increasing `refresh_threshold_minutes` for high-traffic apps

## Testing Token Refresh

To test token refresh in development:

1. Set a very short threshold:
   ```env
   ENTRA_REFRESH_THRESHOLD=55
   ```
   This will refresh almost immediately after login.

2. Watch the logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Make a request after logging in

4. Look for the "Entra token refreshed" log message
