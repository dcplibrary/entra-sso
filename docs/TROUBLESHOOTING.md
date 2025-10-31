# Troubleshooting Guide

This guide covers common issues you may encounter when using the Entra SSO package and how to resolve them.

## Table of Contents

- [Authentication Issues](#authentication-issues)
- [Configuration Problems](#configuration-problems)
- [Group and Role Issues](#group-and-role-issues)
- [Token Refresh Issues](#token-refresh-issues)
- [User Creation Problems](#user-creation-problems)
- [Redirect Issues](#redirect-issues)
- [API Permission Errors](#api-permission-errors)
- [Debugging Tips](#debugging-tips)

---

## Authentication Issues

### "Invalid state parameter. Possible CSRF attack."

**Cause:** State validation failed. This protects against CSRF attacks.

**Solutions:**

1. Check that your session driver is working:
   ```env
   SESSION_DRIVER=file  # or database, redis, etc.
   ```

2. Clear session data:
   ```bash
   php artisan session:clear
   php artisan cache:clear
   ```

3. Ensure cookies are enabled in your browser

4. Check that `APP_URL` matches your actual URL:
   ```env
   APP_URL=https://yourapp.com
   ```

5. Verify session configuration in `config/session.php`:
   ```php
   'same_site' => 'lax',  // not 'strict' for OAuth
   ```

---

### "Authentication failed: AADSTS50011: The redirect URI specified in the request does not match"

**Cause:** The redirect URI in your config doesn't match what's registered in Azure.

**Solutions:**

1. Check your `.env` file:
   ```env
   ENTRA_REDIRECT_URI="${APP_URL}/auth/entra/callback"
   ```

2. Verify the redirect URI in Azure Portal:
   - Go to Azure Portal → Entra ID → App registrations
   - Select your app → Authentication
   - Check that `https://yourapp.com/auth/entra/callback` is listed

3. Ensure the protocol matches (http vs https)

4. Check for trailing slashes (Azure is picky about exact matches)

---

### "Authentication failed: AADSTS700016: Application not found"

**Cause:** The client ID is incorrect or the app doesn't exist.

**Solutions:**

1. Verify your client ID in `.env`:
   ```env
   ENTRA_CLIENT_ID=your-client-id-here
   ```

2. Check that the app exists in Azure Portal → Entra ID → App registrations

3. Ensure you're using the **Application (client) ID**, not the Object ID

---

### "Authentication failed: AADSTS7000215: Invalid client secret"

**Cause:** The client secret is wrong or expired.

**Solutions:**

1. Create a new client secret:
   - Azure Portal → Entra ID → App registrations
   - Select your app → Certificates & secrets
   - New client secret

2. Update your `.env` file with the new secret:
   ```env
   ENTRA_CLIENT_SECRET=your-new-secret
   ```

3. Clear the config cache:
   ```bash
   php artisan config:clear
   ```

---

## Configuration Problems

### "Class 'Dcplibrary\EntraSSO\EntraSSOServiceProvider' not found"

**Cause:** Package not installed or autoload not updated.

**Solutions:**

1. Install the package:
   ```bash
   composer update dcplibrary/entra-sso
   ```

2. Dump autoload:
   ```bash
   composer dump-autoload
   ```

3. Clear config cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

### Configuration not updating

**Cause:** Laravel is caching the config file.

**Solutions:**

```bash
php artisan config:clear
php artisan cache:clear
```

For production:
```bash
php artisan config:cache
```

---

### Environment variables not being read

**Cause:** `.env` file changes not loaded.

**Solutions:**

1. Restart your development server:
   ```bash
   # If using artisan serve
   php artisan serve

   # If using Valet
   valet restart

   # If using Docker
   docker-compose restart
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

---

## Group and Role Issues

### Groups not syncing to users

**Cause:** Group sync is disabled or API permission is missing.

**Solutions:**

1. Enable group sync in config:
   ```php
   'sync_groups' => true,
   'sync_on_login' => true,
   ```

2. Verify API permissions in Azure:
   - Azure Portal → Entra ID → App registrations
   - Select your app → API permissions
   - Ensure `GroupMember.Read.All` is added
   - **Click "Grant admin consent"** (important!)

3. Check Laravel logs for errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

### Wrong role assigned to user

**Cause:** Group mapping order or group name mismatch.

**Solutions:**

1. Check the mapping order (first match wins):
   ```php
   'group_role_mapping' => [
       'IT Administrators' => 'admin',  // Checked first
       'All Staff' => 'user',           // Checked second
   ],
   ```

2. Verify exact group names (case-sensitive):
   - Check Azure Portal → Entra ID → Groups
   - Use Group Object ID for stability:
     ```php
     'group_role_mapping' => [
         '12345678-1234-1234-1234-123456789abc' => 'admin',
     ],
     ```

3. Check that `role` is fillable in User model:
   ```php
   protected $fillable = ['name', 'email', 'role', 'entra_id', 'entra_groups'];
   ```

---

### User groups showing as null

**Cause:** `entra_groups` not properly configured or syncing disabled.

**Solutions:**

1. Check the User model cast:
   ```php
   protected function casts(): array
   {
       return [
           'entra_groups' => 'array',
       ];
   }
   ```

2. Ensure the field is fillable:
   ```php
   protected $fillable = ['entra_groups'];
   ```

3. Verify the migration ran:
   ```bash
   php artisan migrate:status
   ```

4. Check that `sync_groups` is enabled in config

---

## Token Refresh Issues

### "Failed to refresh Entra token"

**Cause:** Refresh token expired, revoked, or invalid.

**Solutions:**

1. User must log in again (refresh tokens expire after ~90 days)

2. Check if user changed their password (invalidates all tokens)

3. Verify `offline_access` scope is being requested:
   ```php
   // In EntraSSOService.php
   $scopes = 'openid profile email User.Read offline_access GroupMember.Read.All';
   ```

4. Check Laravel logs for specific error:
   ```bash
   tail -f storage/logs/laravel.log | grep "refresh token"
   ```

---

### Tokens not stored in session

**Cause:** Token refresh disabled or session issues.

**Solutions:**

1. Enable token refresh:
   ```env
   ENTRA_ENABLE_TOKEN_REFRESH=true
   ```

2. Check session driver is working:
   ```bash
   php artisan session:table  # if using database sessions
   php artisan migrate
   ```

3. Verify session middleware is active on routes

---

## User Creation Problems

### "Users not being created on first login"

**Cause:** `auto_create_users` is disabled.

**Solutions:**

1. Enable auto-create in config:
   ```env
   ENTRA_AUTO_CREATE_USERS=true
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

3. Check Laravel logs for creation errors

---

### "Column 'entra_id' not found"

**Cause:** Migration not run or column name mismatch.

**Solutions:**

1. Check if migration exists:
   ```bash
   ls database/migrations/*entra*
   ```

2. Copy the migration from the package:
   ```bash
   cp vendor/dcplibrary/entra-sso/database/migrations/*_add_entra_fields_to_users.php database/migrations/
   ```

3. Run migrations:
   ```bash
   php artisan migrate
   ```

4. Verify columns exist:
   ```bash
   php artisan tinker
   >>> Schema::hasColumn('users', 'entra_id')
   ```

---

### "Mass assignment exception" for entra fields

**Cause:** Fields not added to `$fillable` array.

**Solutions:**

Edit `app/Models/User.php`:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'entra_id',
    'role',
    'entra_groups',
    'entra_custom_claims',
];
```

---

## Redirect Issues

### Redirects to wrong URL after login

**Cause:** Hardcoded redirect in the controller.

**Solutions:**

1. The default redirect is `/dashboard` in `EntraSSOController.php:58`

2. To customize, extend the controller:
   ```php
   namespace App\Http\Controllers;

   use Dcplibrary\EntraSSO\Http\Controllers\EntraSSOController as BaseController;

   class CustomEntraSSOController extends BaseController
   {
       public function callback(Request $request)
       {
           $response = parent::callback($request);

           // Customize redirect logic here
           return redirect()->intended('/home');
       }
   }
   ```

3. Update your routes to use the custom controller

---

### OAuth callback returns 404

**Cause:** Routes not registered.

**Solutions:**

1. Ensure the service provider is registered (should be automatic)

2. Check routes are loaded:
   ```bash
   php artisan route:list | grep entra
   ```

3. Clear route cache:
   ```bash
   php artisan route:clear
   ```

---

## API Permission Errors

### "Insufficient privileges to complete the operation"

**Cause:** Missing or not granted API permissions in Azure.

**Solutions:**

1. Add required permissions in Azure Portal:
   - App registrations → Your app → API permissions
   - Add: `User.Read`, `GroupMember.Read.All`

2. **Grant admin consent** (click the button!)

3. Wait 5-10 minutes for permissions to propagate

4. For `GroupMember.Read.All`, Application permission (not Delegated) is recommended

---

### "Access denied" when fetching groups

**Cause:** Missing `GroupMember.Read.All` permission or admin consent.

**Solutions:**

1. Add the permission in Azure Portal

2. Click "Grant admin consent for [Tenant]"

3. Verify permission type is "Application" not "Delegated"

4. If delegated, ensure the logged-in user has permission to read groups

---

## Debugging Tips

### Enable detailed logging

Add to your controller or service to log OAuth responses:

```php
\Log::info('Token Data:', $tokenData);
\Log::info('User Info:', $userInfo);
\Log::info('Groups:', $groups);
```

---

### Inspect session data

```bash
php artisan tinker
>>> session()->all()
```

Or in your controller:
```php
dd(session()->all());
```

---

### Test API permissions manually

Use Microsoft Graph Explorer to test if your app has the right permissions:

https://developer.microsoft.com/en-us/graph/graph-explorer

---

### Check Laravel logs

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Search for specific errors
grep "Entra" storage/logs/laravel.log

# Search for today's errors
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log
```

---

### Verify OAuth flow manually

1. Get the authorization URL:
   ```bash
   php artisan tinker
   >>> app(Dcplibrary\EntraSSO\EntraSSOService::class)->getAuthorizationUrl()
   ```

2. Visit the URL in your browser

3. Check the callback URL and parameters

---

### Test token refresh

Force a token refresh to test the functionality:

```php
use Dcplibrary\EntraSSO\EntraSSOService;

Route::get('/test-refresh', function (EntraSSOService $sso) {
    $refreshToken = session('entra_refresh_token');

    try {
        $tokenData = $sso->refreshAccessToken($refreshToken);
        return 'Success: ' . json_encode($tokenData);
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});
```

---

### Clear everything and start fresh

If all else fails, clear all caches and restart:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan session:clear
composer dump-autoload

# Restart the dev server
php artisan serve
```

---

## Getting Help

If you're still experiencing issues:

1. **Check Laravel logs:** `storage/logs/laravel.log`
2. **Enable debug mode:** Set `APP_DEBUG=true` in `.env` (development only!)
3. **Review Azure audit logs:** Azure Portal → Entra ID → Sign-ins
4. **Test with Graph Explorer:** https://developer.microsoft.com/graph/graph-explorer
5. **Check package issues:** https://github.com/dcplibrary/entra-sso/issues

---

## Common Error Codes

| Error Code | Meaning | Solution |
|------------|---------|----------|
| AADSTS50011 | Redirect URI mismatch | Fix redirect URI in Azure and config |
| AADSTS700016 | Application not found | Check client ID |
| AADSTS7000215 | Invalid client secret | Generate new secret in Azure |
| AADSTS65001 | User consent required | Add admin consent in Azure |
| AADSTS65004 | User declined consent | User must accept permissions |
| AADSTS90014 | Required field missing | Check request parameters |
| AADSTS50105 | User not assigned to app | Add user in Azure Enterprise Apps |

For more error codes, see: https://docs.microsoft.com/azure/active-directory/develop/reference-aadsts-error-codes
