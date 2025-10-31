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
# Typical development setup uses a path repository in a parent Laravel project:
# In the consuming Laravel app's composer.json:
# "repositories": [{"type": "path", "url": "./entra-sso"}]
# "require": {"dcplibrary/entra-sso": "*"}

# Then in the Laravel app:
composer update dcplibrary/entra-sso

# Publish config to test changes
php artisan vendor:publish --tag=entra-sso-config

# Run migrations
php artisan migrate
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
- Override `group_role_mapping` in config for organization-specific groups
- Add custom claims mapping for organization-specific attributes
- Extend the User model to add helper methods using `entra_groups` or `entra_custom_claims`
- Configure `role_model` if using a package like Spatie Permission