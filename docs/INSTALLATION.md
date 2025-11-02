# Installation Guide

This guide covers the installation process for the Entra SSO package in Laravel applications.

## Prerequisites

- PHP 8.2 or higher
- Laravel 12.0 or higher
- Composer
- Azure AD tenant with admin access (see [AZURE_SETUP.md](AZURE_SETUP.md))

## Quick Installation (Recommended)

The easiest way to install and configure Entra SSO is using the interactive wizard:

```bash
composer require dcplibrary/entra-sso
php artisan entra:install
```

The wizard will:
- Prompt for Azure AD credentials
- Update your `.env` file
- Automatically modify `app/Models/User.php`
- Run migrations
- Optionally publish config and views

**For starter kit conflicts:**
```bash
php artisan entra:install --fix-starter-kit
```

See [STARTER_KITS.md](STARTER_KITS.md) for details on Breeze/Jetstream/Livewire/Inertia compatibility.

## Manual Installation

If you prefer to configure everything manually:

### Step 1: Install Package

**Production:**
```bash
composer require dcplibrary/entra-sso
```

**Development (local path):**

Add to your Laravel project's `composer.json`:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../entra-sso"
        }
    ],
    "require": {
        "dcplibrary/entra-sso": "*"
    }
}
```

Then run:
```bash
composer update dcplibrary/entra-sso
```

### Step 2: Configure Environment

Add these variables to your `.env` file:

```env
ENTRA_TENANT_ID=your-tenant-id
ENTRA_CLIENT_ID=your-client-id
ENTRA_CLIENT_SECRET=your-client-secret
ENTRA_REDIRECT_URI="${APP_URL}/auth/entra/callback"

# User Management
ENTRA_AUTO_CREATE_USERS=true

# Redirect after successful login (default: /entra/dashboard)
ENTRA_REDIRECT_AFTER_LOGIN=/entra/dashboard

# Group & Role Sync
ENTRA_SYNC_GROUPS=true
ENTRA_SYNC_ON_LOGIN=true
ENTRA_DEFAULT_ROLE=user

# Group to Role Mapping (simple format)
ENTRA_GROUP_ROLES="IT Admins:admin,Developers:developer,Staff:user"

# Token Refresh
ENTRA_ENABLE_TOKEN_REFRESH=true
ENTRA_REFRESH_THRESHOLD=5

# Custom Claims
ENTRA_STORE_CUSTOM_CLAIMS=false
```

**Get your Azure AD credentials:** See [AZURE_SETUP.md](AZURE_SETUP.md) for detailed instructions.

### Step 3: Run Migrations

The package migrations will run automatically:

```bash
php artisan migrate
```

This adds these fields to your `users` table:
- `entra_id` (string, unique, nullable)
- `role` (string, nullable)
- `entra_groups` (JSON, nullable)
- `entra_custom_claims` (JSON, nullable)

### Step 4: Update User Model

Edit `app/Models/User.php` and make **two required changes**:

**Change #1 - Import and extend the package User model:**
```php
// Change this:
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable

// To this:
use Dcplibrary\EntraSSO\Models\User as EntraUser;

class User extends EntraUser
```

**Change #2 - Merge parent casts:**
```php
// Change this:
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}

// To this:
protected function casts(): array
{
    return array_merge(parent::casts(), [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ]);
}
```

**Complete example:**
```php
<?php

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
        // are automatically included via getFillable() - no need to add them here
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ]);
    }
}
```

**Why the casts() merge is required:**
- Without `array_merge(parent::casts(), ...)`, the child class completely overrides parent casts
- This causes `entra_groups` and `entra_custom_claims` to be returned as JSON strings instead of arrays
- All helper methods (`inGroup()`, `hasRole()`, `getCustomClaim()`, etc.) will fail

The base `EntraUser` class provides:
- Automatic casting for `entra_groups` and `entra_custom_claims`
- Helper methods like `hasRole()`, `inGroup()`, `isAdmin()`, `isManager()`
- Custom claim accessors
- Automatic fillable field merging

### Step 5: (Optional) Publish Assets

The package works out of the box without publishing anything. However, you can publish assets if needed:

**Publish config** (only for advanced scenarios):
```bash
php artisan vendor:publish --tag=entra-sso-config
```

Publish config if you need:
- Complex group mappings (multiple groups → one role)
- Custom claims field mapping
- Role model integration (e.g., Spatie Permissions)

For simple group-to-role mapping, use `ENTRA_GROUP_ROLES` in `.env` instead.

**Publish views** (only if customizing login view):
```bash
php artisan vendor:publish --tag=entra-sso-views
```

## Command Options

The `entra:install` command supports several options:

```bash
# Automatically detect and fix starter kit conflicts
php artisan entra:install --fix-starter-kit

# Skip User model modifications (if already done)
php artisan entra:install --skip-user-model

# Skip environment variable setup (if already configured)
php artisan entra:install --skip-env

# Force overwrite existing configuration
php artisan entra:install --force
```

## Testing the Installation

### 1. Start your Laravel app:
```bash
php artisan serve
```

### 2. Visit the Entra login route:
```
http://localhost:8000/auth/entra
```

You should be redirected to Microsoft login!

### 3. After successful login:
Users are redirected to `/entra/dashboard` by default, which displays:
- User information from Azure AD
- Azure AD groups
- Group-to-role mappings
- Available routes
- Code examples

## Fresh vs Existing Laravel Install

**Fresh Laravel Install (Recommended):**
```bash
composer create-project laravel/laravel my-app
cd my-app
composer require dcplibrary/entra-sso
php artisan entra:install
```

**Existing Laravel Install:**
- ✅ Works with existing User model (gets extended automatically)
- ✅ Works with existing authentication
- ✅ Works with existing migrations
- ⚠️ Backup your `app/Models/User.php` first (wizard creates backup)
- ⚠️ Review the User model changes after running `entra:install`

## Adding Login Button

Add a login button to your views:

```blade
<a href="{{ route('entra.login') }}">Sign in with Microsoft</a>
```

Or redirect your `/login` route to Entra:

```php
// routes/web.php
Route::get('/login', function () {
    return redirect()->route('entra.login');
})->name('login');
```

## Next Steps

After installation, explore these features:

- **Azure AD Setup:** [AZURE_SETUP.md](AZURE_SETUP.md) - Configure your Azure AD application
- **Role Mapping:** [ROLE_MAPPING.md](ROLE_MAPPING.md) - Map Azure AD groups to application roles
- **Custom Claims:** [CUSTOM_CLAIMS.md](CUSTOM_CLAIMS.md) - Include additional user attributes
- **Token Refresh:** [TOKEN_REFRESH.md](TOKEN_REFRESH.md) - Enable automatic token refresh for long sessions
- **Starter Kits:** [STARTER_KITS.md](STARTER_KITS.md) - Configure Breeze/Jetstream/Livewire/Inertia
- **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Common issues and solutions

## Troubleshooting Installation

### Package not found
```bash
composer require dcplibrary/entra-sso --update-with-dependencies
```

### Migrations not running
```bash
php artisan migrate:status
php artisan migrate
```

### Config not updating
```bash
php artisan config:clear
php artisan cache:clear
```

### Service provider not registered
The package uses Laravel's auto-discovery. If issues persist:
```bash
composer dump-autoload
php artisan config:clear
```

For more issues, see [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
