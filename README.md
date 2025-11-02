# Entra SSO Package for Laravel

A simple, reusable Entra (Azure AD) Single Sign-On package for Laravel 12 with role mapping, group sync, token refresh, and custom claims support.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
  - [Framework Compatibility](#framework-compatibility)
  - [Azure AD Setup](#azure-ad-setup)
  - [Fresh vs Existing Laravel Install](#fresh-vs-existing-laravel-install)
- [Installation](#installation)
  - [Quick Install (Recommended)](#quick-install-recommended)
  - [Manual Installation](#manual-installation)
  - [Command Options](#command-options)
- [Usage](#usage)
  - [Login Button](#login-button)
  - [Protect Routes](#protect-routes)
  - [Using with Existing Authentication](#using-with-existing-authentication)
  - [Group to Role Mapping](#group-to-role-mapping)
  - [Custom Claims](#custom-claims)
  - [Token Refresh](#token-refresh)
- [Starter Kit Configuration](#starter-kit-configuration)
  - [React/Vue Starter Kits](#reactvue-starter-kits-inertia--breeze)
  - [Livewire Starter Kit](#livewire-starter-kit-fortify)
  - [Laravel Breeze](#laravel-breeze)
  - [Laravel Jetstream](#laravel-jetstream)
- [Development](#development)
  - [Local Package Development](#local-package-development)
  - [Contributing](#contributing)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## Features

- ✅ Easy Azure AD/Entra authentication
- ✅ Auto-create users on first login
- ✅ Group synchronization and role mapping
- ✅ Automatic token refresh for long sessions
- ✅ Custom claims handling
- ✅ State validation for CSRF protection
- ✅ Configurable via environment variables
- ✅ Works across multiple Laravel instances

## Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 12.0 or higher
- **Session Driver**: Any (database, redis, file, etc.)
- **Database**: Any supported by Laravel (MySQL, PostgreSQL, SQLite, etc.)

### Azure AD Setup

Before installing the package, you'll need to configure an application in Azure AD:

1. Register an application in Azure AD
2. Configure redirect URIs
3. Generate a client secret
4. Note your Tenant ID and Client ID

**📖 Detailed setup instructions:** [Azure AD Configuration Guide](docs/AZURE_SETUP.md)

### Framework Compatibility

This package is **framework-agnostic** and works with all Laravel frontend stacks:

| Starter Kit | Compatible | Notes |
|-----------|-----------|-------|
| **None (Blade only)** | ✅ Yes | Recommended - zero conflicts |
| **React (Inertia)** | ⚠️ Conflicts | Uses Breeze auth - see note below |
| **Vue (Inertia)** | ⚠️ Conflicts | Uses Breeze auth - see note below |
| **Livewire** | ⚠️ Conflicts | Uses Fortify auth - see note below |
| **Laravel Breeze** | ⚠️ Conflicts | See note below |
| **Laravel Jetstream** | ⚠️ Conflicts | See note below |

**⚠️ Important: Starter Kit Conflicts**

Laravel starter kits provide authentication features that **conflict** with Entra SSO:
- Competing login routes (`/login`)
- Email verification (redundant - Azure AD verifies emails)
- Password management (redundant - Azure AD manages passwords)
- Two-factor authentication (redundant - Azure AD provides MFA)

**All starter kits from `laravel new` include authentication**, which conflicts with Entra SSO:
- **React** → Installs Laravel Breeze with Inertia + React
- **Vue** → Installs Laravel Breeze with Inertia + Vue
- **Livewire** → Installs Livewire with Fortify authentication

**Recommended Installation Approaches:**

**Option 1: None (No starter kit) - Recommended**
```bash
laravel new my-app
# When prompted: Select "None"
cd my-app
composer require dcplibrary/entra-sso
php artisan entra:install
```
This is the cleanest approach with zero conflicts. You can still use React/Vue/Livewire by installing them separately without auth.

**Option 2: Starter kit with auto-fix**
```bash
laravel new my-app
# Select React, Vue, or Livewire
cd my-app
composer require dcplibrary/entra-sso
php artisan entra:install --fix-starter-kit
```
The install command will detect and fix authentication conflicts automatically.

**Option 3: Manual installation (existing project)**
If you already have a starter kit installed, see [Starter Kit Configuration](#starter-kit-configuration) below.

### Fresh vs Existing Laravel Install

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
- ⚠️ Backup your `app/Models/User.php` first (command creates backup)
- ⚠️ Review the User model changes after running `entra:install`

## Installation

### Quick Install (Recommended)

Install via Composer and run the interactive setup wizard:

```bash
composer require dcplibrary/entra-sso
php artisan entra:install
```

The `entra:install` command will:
- Prompt for Azure AD credentials and add to `.env`
- Automatically update `app/Models/User.php` to extend EntraUser
- Fix the `casts()` method to merge with parent
- Run migrations
- Optionally publish config and views

### Manual Installation

If you prefer to set up manually:

#### 1. Install Package

```bash
composer require dcplibrary/entra-sso
```

#### 2. Environment Variables

The package configuration is automatically available. Just add these variables to your `.env` file:

```env
ENTRA_TENANT_ID=your-tenant-id
ENTRA_CLIENT_ID=your-client-id
ENTRA_CLIENT_SECRET=your-client-secret
ENTRA_REDIRECT_URI="${APP_URL}/auth/entra/callback"
ENTRA_AUTO_CREATE_USERS=true

# Group & Role Sync
ENTRA_SYNC_GROUPS=true
ENTRA_SYNC_ON_LOGIN=true
ENTRA_DEFAULT_ROLE=user

# Group to Role Mapping (simple format)
# Maps Azure AD group names to application roles
ENTRA_GROUP_ROLES="IT Admins:admin,Developers:developer,Staff:user"

# Token Refresh
ENTRA_ENABLE_TOKEN_REFRESH=true
ENTRA_REFRESH_THRESHOLD=5

# Custom Claims
ENTRA_STORE_CUSTOM_CLAIMS=false
```

#### 3. Run Migrations

The package migrations will run automatically. Just run:
```bash
php artisan migrate
```

#### 4. Update User Model

Edit `app/Models/User.php` and make these **two required changes**:

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
        // Note: Entra fields are automatically included via getFillable()
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

#### 5. (Optional) Publish Assets

The package works out of the box without publishing anything. However, you can publish assets if you need to customize them:

**Publish config** (only needed for advanced scenarios):
```bash
php artisan vendor:publish --tag=entra-sso-config
```

Publish config if you need:
- Complex group mappings (multiple groups → one role)
- Custom claims field mapping
- Role model integration (e.g., Spatie Permissions)

For simple group-to-role mapping, use `ENTRA_GROUP_ROLES` in `.env` instead.

**Publish views** (only if you need to customize the login view):
```bash
php artisan vendor:publish --tag=entra-sso-views
```

### Command Options

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

The wizard will automatically detect Breeze/Jetstream/Fortify and prompt to fix conflicts.

## Usage

### Login Button

```blade
<a href="{{ route('entra.login') }}">Sign in with Microsoft</a>
```

### Protect Routes

```php
// By role
Route::middleware(['auth', 'entra.role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// By Entra group
Route::middleware(['auth', 'entra.group:IT Admins'])->group(function () {
    Route::get('/servers', [ServerController::class, 'index']);
});
```

### Using with Existing Authentication

You can use Entra SSO alongside traditional email/password authentication:

**Option 1: Redirect to Entra SSO**
```php
// routes/web.php
Route::get('/login', function () {
    return redirect()->route('entra.login');
});
```

**Option 2: Add SSO button to existing login page**
```blade
<!-- resources/views/auth/login.blade.php -->
<div class="mb-4">
    <a href="{{ route('entra.login') }}" class="btn btn-primary w-full">
        Sign in with Microsoft
    </a>
</div>

<div class="divider">OR</div>

<!-- Your existing email/password form -->
<form method="POST" action="{{ route('login') }}">
    <!-- ... -->
</form>
```

**Option 3: Use only Entra SSO**
```php
// config/auth.php - No changes needed!
// The package works with Laravel's default auth setup
```

### Group to Role Mapping

Map Azure AD groups to application roles using the `.env` file:

```env
ENTRA_GROUP_ROLES="IT Admins:admin,Developers:developer,Staff:user"
```

**How it works:**
- When a user logs in, their Azure AD groups are synced
- Groups are matched against the `ENTRA_GROUP_ROLES` mapping
- The first matching group sets the user's role
- If no groups match, the `ENTRA_DEFAULT_ROLE` is used

**Advanced mapping** (requires publishing config):

If you need complex mappings (multiple groups → one role), publish the config:

```bash
php artisan vendor:publish --tag=entra-sso-config
```

Then edit `config/entra-sso.php`:

```php
'group_role_mapping' => [
    'IT Admins' => 'admin',
    'Computer Services' => 'admin',  // Multiple groups can map to same role
    'Developers' => 'developer',
    'Staff' => 'user',
],
```

**📖 Learn more:** [Advanced Role Mapping Guide](docs/ROLE_MAPPING.md)

### Custom Claims

Azure AD can provide custom claims beyond standard user information (name, email). You can map these claims to your User model or store them for later use.

**Configure in `.env`:**
```env
ENTRA_STORE_CUSTOM_CLAIMS=true
```

**Access custom claims:**
```php
$department = auth()->user()->getCustomClaim('department');
$jobTitle = auth()->user()->getCustomClaim('jobTitle', 'Unknown');

if (auth()->user()->hasCustomClaim('employeeId')) {
    // User has employee ID claim
}
```

**📖 Learn more:** [Custom Claims Configuration Guide](docs/CUSTOM_CLAIMS.md)

### Token Refresh

The package can automatically refresh access tokens before they expire, ensuring uninterrupted access for long sessions.

**Configure in `.env`:**
```env
ENTRA_ENABLE_TOKEN_REFRESH=true
ENTRA_REFRESH_THRESHOLD=5  # Refresh 5 minutes before expiry
```

Tokens are stored in the session and automatically refreshed when they're about to expire.

**📖 Learn more:** [Token Refresh Details](docs/TOKEN_REFRESH.md)

## Starter Kit Configuration

If you already have a Laravel starter kit installed, you'll need to configure it to work with Entra SSO.

**Auto-detection:** The `entra:install` command automatically detects and can fix these configurations. Run with `--fix-starter-kit` flag for non-interactive fixing.

### React/Vue Starter Kits (Inertia + Breeze)

The React and Vue options from `laravel new` install **Laravel Breeze with Inertia**. Follow the same steps as Laravel Breeze below.

The `entra:install` command will automatically detect Breeze (via `routes/auth.php`) and offer to fix conflicts.

### Livewire Starter Kit (Fortify)

The Livewire starter kit uses Laravel Fortify for authentication. Make these changes:

**1. Disable Fortify's login views** (`config/fortify.php`):
```php
'views' => false,  // Change from true to false
```
This prevents Fortify from registering its own `/login` route.

**2. Remove email verification middleware** (`routes/web.php`):
```php
// Before:
Route::get('/dashboard', function () {
    //...
})->middleware(['auth', 'verified']);

// After (remove 'verified'):
Route::get('/dashboard', function () {
    //...
})->middleware(['auth']);
```
Azure AD already verifies emails, so this middleware is redundant and blocks SSO users.

**3. (Optional) Disable unused Fortify features** (`config/fortify.php`):
```php
'features' => [
    // Features::registration(),     // Disabled - users created via SSO
    // Features::resetPasswords(),   // Disabled - Azure AD manages passwords
    // Features::emailVerification(), // Disabled - Azure AD verifies emails
    // Features::updateProfileInformation(), // Disabled - managed via Azure AD
    // Features::updatePasswords(),   // Disabled - Azure AD manages passwords
    // Features::twoFactorAuthentication([  // Disabled - Azure AD provides MFA
    //     'confirm' => true,
    //     'confirmPassword' => true,
    // ]),
],
```

### Laravel Breeze

**1. Remove Breeze routes** (`routes/auth.php` or `routes/web.php`):
```php
// Comment out or remove all Breeze auth routes
// require __DIR__.'/auth.php';
```

**2. Redirect login to Entra** (`routes/web.php`):
```php
Route::get('/login', function () {
    return redirect()->route('entra.login');
})->name('login');
```

**3. Remove email verification middleware** (same as Fortify above).

### Laravel Jetstream

**1. Disable Jetstream features** (`config/jetstream.php`):
```php
'features' => [
    // Features::termsAndPrivacyPolicy(),
    // Features::profilePhotos(),
    // Features::api(),
    // Features::teams(['invitations' => true]),
    // Features::accountDeletion(),
],
```

**2. Redirect login** (same as Breeze above).

**3. Remove verification middleware** (same as above).

### Why These Changes Are Needed

Azure AD/Entra SSO already provides:
- ✅ **User authentication** - No need for password-based login
- ✅ **Email verification** - Microsoft verifies all emails
- ✅ **Password management** - Handled by Azure AD
- ✅ **Two-factor authentication** - Azure AD provides MFA
- ✅ **Password reset** - Managed through Azure AD portal
- ✅ **Profile management** - Managed through Azure AD

Starter kits provide these same features, creating conflicts and redundancy.

## Development

### Local Package Development

If you're developing this package locally and want to test changes in a Laravel application, you can use the automated setup script or do it manually.

#### Option 1: Automated Setup (Recommended)

1. Clone this repository alongside your Laravel app:
```bash
cd /path/to/your/projects
git clone https://github.com/dcplibrary/entra-sso.git
cd your-laravel-app
```

2. Run the development setup script:
```bash
bash ../entra-sso/setup-dev.sh
```

The script will:
- Add path repository to composer.json
- Install the package locally
- Update User model to extend EntraUser
- Add environment variables
- Run migrations
- Optionally publish config and views

**Important:** The script will prompt you to manually fix the `casts()` method in `app/Models/User.php`. This is required!

#### Option 2: Manual Setup

1. Clone this repository alongside your Laravel app:
```bash
cd /path/to/your/projects
git clone https://github.com/dcplibrary/entra-sso.git
cd your-laravel-app
```

2. Add to your Laravel app's `composer.json`:
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

3. Install or update the package:
```bash
composer update dcplibrary/entra-sso
```

4. Follow the installation steps from the main README (environment variables, User model updates, migrations)

5. Make changes in the `entra-sso` directory, then refresh in your Laravel app:
```bash
# Publish updated config if needed
php artisan vendor:publish --tag=entra-sso-config --force

# Clear caches
php artisan config:clear
php artisan cache:clear
```

### Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

## Troubleshooting

Having issues? Check our comprehensive troubleshooting guide:

**Common issues covered:**
- Login redirects not working
- "Invalid state parameter" errors
- User not being created automatically
- Groups not syncing
- Token refresh failures
- Middleware permission errors

**📖 Full troubleshooting guide:** [Troubleshooting Guide](docs/TROUBLESHOOTING.md)

**Still stuck?** [Open an issue on GitHub](https://github.com/dcplibrary/entra-sso/issues)

## License

MIT
