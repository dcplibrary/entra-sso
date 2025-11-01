# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 package (`dcplibrary/entra-sso`) that provides Microsoft Entra (formerly Azure AD) Single Sign-On authentication. It's designed as a reusable library that can be installed in multiple Laravel applications.

**Package namespace**: `Dcplibrary\EntraSSO`

## Development Commands

Since this is a package (not a standalone application), there is no local build/test system. Development workflow:

```bash
# Install dependencies
composer install

# The package is meant to be installed in a Laravel app via composer
# Production: composer require dcplibrary/entra-sso
# Development: Use path repository in parent Laravel project

# For local development, in the consuming Laravel app's composer.json:
# "repositories": [{"type": "path", "url": "./entra-sso"}]
# "require": {"dcplibrary/entra-sso": "*"}

# Then in the Laravel app:
composer update dcplibrary/entra-sso

# Run interactive installation wizard (recommended)
php artisan entra:install

# Or manually:
# Run migrations (package migrations run automatically)
php artisan migrate

# Optional: Publish config only if you need to test config customizations
php artisan vendor:publish --tag=entra-sso-config

# Optional: Publish views only if you need to test view customizations
php artisan vendor:publish --tag=entra-sso-views
```

## Console Commands

The package provides the following Artisan commands:

### `entra:install`
Interactive installation wizard that automates the setup process:
- Prompts for Azure AD credentials
- Updates `.env` file with configuration
- Automatically modifies `app/Models/User.php` to extend EntraUser
- Fixes `casts()` method to merge with parent
- Runs migrations
- Optionally publishes config and views

**Options:**
- `--skip-user-model` - Skip User model modifications
- `--skip-env` - Skip environment variable setup
- `--force` - Overwrite existing configuration

**Example:**
```bash
php artisan entra:install
php artisan entra:install --skip-user-model
php artisan entra:install --force
```

## Architecture

### Core Service Layer
- **EntraSSOService** (`src/EntraSSOService.php`): Core service handling all OAuth2/OIDC interactions with Microsoft Entra
  - Authorization URL generation with CSRF state protection
  - Token exchange (authorization code -> access token)
  - Token refresh using refresh tokens
  - Microsoft Graph API calls (user info, groups, roles)
  - ID token parsing for custom claims
  - Scopes requested: `openid profile email User.Read offline_access GroupMember.Read.All`

### Controller Layer
- **EntraSSOController** (`src/Http/Controllers/EntraSSOController.php`): Handles OAuth flow
  - `redirect()`: Initiates OAuth flow
  - `callback()`: Processes OAuth callback, creates/updates users, syncs groups/roles
  - `logout()`: Handles logout
  - User creation/update logic with auto-create option
  - Group sync and role mapping during login
  - Custom claims extraction and mapping

### Middleware
Three middleware classes provide authorization:

1. **CheckRole** (`src/Http/Middleware/CheckRole.php`):
   - Checks user's role (supports `$user->role` string or role relationships)
   - Usage: `Route::middleware('entra.role:admin,manager')`

2. **CheckGroup** (`src/Http/Middleware/CheckGroup.php`):
   - Checks if user belongs to specific Entra groups (stored in `$user->entra_groups`)
   - Usage: `Route::middleware('entra.group:IT Admins,Developers')`

3. **RefreshEntraToken** (`src/Http/Middleware/RefreshEntraToken.php`):
   - Automatically refreshes access tokens before expiration
   - Runs on every request when `enable_token_refresh` is enabled
   - Stores tokens in session: `entra_access_token`, `entra_refresh_token`, `entra_token_expires_at`
   - Threshold configurable via `refresh_threshold_minutes` (default: 5 minutes before expiry)

### Service Provider
- **EntraSSOServiceProvider** (`src/EntraSSOServiceProvider.php`):
  - Registers `EntraSSOService` as singleton
  - Publishes config file with tag `entra-sso-config`
  - Loads routes from `routes/web.php`
  - Registers middleware aliases: `entra.role`, `entra.group`, `entra.refresh`
  - Conditionally adds `RefreshEntraToken` to web middleware group if enabled

### Routes
- `GET /auth/entra` → `entra.login` (initiates OAuth)
- `GET /auth/entra/callback` → `entra.callback` (OAuth callback)
- `POST /auth/entra/logout` → `entra.logout` (logout)

All routes use `web` middleware group.

### Configuration
Config file: `config/entra-sso.php`

Key configuration options:
- **OAuth credentials**: `tenant_id`, `client_id`, `client_secret`, `redirect_uri`
- **User management**: `auto_create_users`, `user_model`
- **Group sync**: `sync_groups`, `sync_on_login`, `group_role_mapping`
- **Role handling**: `default_role`, `role_model` (supports both string-based and relationship-based roles)
- **Token refresh**: `enable_token_refresh`, `refresh_threshold_minutes`
- **Custom claims**: `custom_claims_mapping`, `store_custom_claims`

**Group to Role Mapping** can be configured in two ways:

1. **Simple .env mapping** (recommended for most cases):
   ```env
   ENTRA_GROUP_ROLES="IT Admins:admin,Developers:developer,Staff:user"
   ```
   - Easy to configure via environment variables
   - No config file publishing needed
   - Suitable for simple 1:1 group-to-role mappings

2. **Config file mapping** (for advanced scenarios):
   - Publish config: `php artisan vendor:publish --tag=entra-sso-config`
   - Edit `config/entra-sso.php` to set `group_role_mapping` array
   - Allows multiple groups to map to same role
   - Supports complex mapping logic

### User Model
The package provides a base User model (`src/Models/User.php`) that Laravel apps should extend:

**Package User Model** (`Dcplibrary\EntraSSO\Models\User`):
- Extends `Illuminate\Foundation\Auth\User`
- Provides Entra SSO-specific helper methods
- Defines fillable: `entra_id`, `role`, `entra_groups`, `entra_custom_claims`
- Casts `entra_groups` and `entra_custom_claims` as arrays

**Helper Methods Available**:
- `hasRole(string $role): bool` - Check if user has specific role
- `hasAnyRole(array $roles): bool` - Check if user has any of given roles
- `isAdmin(): bool` - Check if user is admin
- `isManager(): bool` - Check if user is manager
- `inGroup(string $groupName): bool` - Check if user belongs to Entra group
- `inAnyGroup(array $groups): bool` - Check if user belongs to any of given groups
- `getCustomClaim(string $claimName, $default = null)` - Get custom claim value
- `hasCustomClaim(string $claimName): bool` - Check if custom claim exists
- `getEntraGroups(): array` - Get all Entra groups
- `getCustomClaims(): array` - Get all custom claims

**App User Model Integration**:
Laravel apps should extend the package User model. **Two changes are required** to your default Laravel User model:

```php
namespace App\Models;

use Dcplibrary\EntraSSO\Models\User as EntraUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends EntraUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        // Note: Entra fields (entra_id, role, entra_groups, entra_custom_claims)
        // are automatically merged via parent's getFillable() method - no need to add them here
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * ⚠️ REQUIRED CHANGE: Must merge with parent::casts() to preserve Entra functionality
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [  // ← ADD array_merge(parent::casts(), here
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ]);
    }
}
```

**CRITICAL: What You Must Change in Your Laravel App's User Model**:

✅ **REQUIRED CHANGE #1** - Add import and change class extension:
```php
// ❌ DEFAULT Laravel User model
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable

// ✅ REQUIRED Fix - Import EntraUser and extend it
use Dcplibrary\EntraSSO\Models\User as EntraUser;

class User extends EntraUser
```

✅ **REQUIRED CHANGE #2** - Change the `casts()` method:
```php
// ❌ DEFAULT Laravel - This will BREAK Entra SSO
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}

// ✅ REQUIRED Fix - Add array_merge(parent::casts(), ...)
protected function casts(): array
{
    return array_merge(parent::casts(), [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ]);
}
```

**Why this change is mandatory:**
- Without `array_merge(parent::casts(), ...)`, the child class completely overrides parent casts
- This causes `entra_groups` and `entra_custom_claims` to be returned as JSON strings instead of arrays
- All helper methods (`inGroup()`, `hasRole()`, `getCustomClaim()`, etc.) will fail
- There is no way for the package to prevent this override - Laravel's inheritance requires explicit merging

**What you DON'T need to change:**
- ✅ `$fillable` - Entra fields are automatically merged via the package's `getFillable()` override
- ✅ `$hidden` - Can stay as-is
- ✅ Class name (`User`), namespace (`App\Models`) - Keep your existing names
- ✅ Traits (`HasFactory`, `Notifiable`) - Keep your existing traits
- ✅ Helper methods - Automatically inherited, no need to add anything

**Summary of Required Changes:**
1. Add `use Dcplibrary\EntraSSO\Models\User as EntraUser;` import
2. Change `class User extends Authenticatable` to `class User extends EntraUser`
3. Change `casts()` method to merge with `parent::casts()`

### Database
Migration adds these fields to users table:
- `entra_id` (string, unique, nullable)
- `role` (string, nullable)
- `entra_groups` (JSON, nullable)
- `entra_custom_claims` (JSON, nullable)

### Key Patterns

**Dual role system support**: The package supports both:
1. Simple string-based roles (`$user->role` field)
2. Relationship-based roles (via `syncRoles()`, `roles()`, or `getRoleNames()` methods)

**Group to role mapping**: Azure AD groups are mapped to application roles via `group_role_mapping` config. Groups are identified by display name or ID.

**Custom claims**: ID token claims (beyond standard OIDC claims) can be:
1. Mapped to user model attributes via `custom_claims_mapping`
2. Stored as JSON in `entra_custom_claims` field if `store_custom_claims` is true

**State validation**: CSRF protection via session-stored state parameter that's validated on callback.

**Token storage**: Access and refresh tokens stored in session (not database) for automatic refresh.

## Important Notes

- This is a **package**, not an application. Always test changes in a parent Laravel application.
- The package auto-registers via Laravel's package discovery (`extra.laravel.providers` in `composer.json`).
- When making changes to middleware, remember the `RefreshEntraToken` middleware is conditionally added to the global `web` group.
- The callback route redirects to `/dashboard` by default on success - this may need to be configurable for different Laravel apps.
- Group sync only happens on login (if `sync_on_login` is true) or when users are created.
- The package uses Laravel's HTTP client (wrapper around Guzzle) for all API calls.

## Common Customizations

When extending this package in a Laravel app:
- **User Model**: Extend `Dcplibrary\EntraSSO\Models\User` in your app's User model (see User Model section above for proper integration)
- **Group Mapping**: Override `group_role_mapping` in config for organization-specific groups
- **Custom Claims**: Add custom claims mapping for organization-specific attributes via `custom_claims_mapping`
- **Role System**: Configure `role_model` if using a package like Spatie Permission for relationship-based roles
- **Helper Methods**: Use built-in helper methods like `hasRole()`, `inGroup()`, `getCustomClaim()` in your app logic
- **Middleware**: Apply `entra.role` and `entra.group` middleware to protect routes based on Entra roles/groups