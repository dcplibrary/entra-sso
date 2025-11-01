# Entra SSO Package for Laravel

A simple, reusable Entra (Azure AD) Single Sign-On package for Laravel 12 with role mapping, group sync, token refresh, and custom claims support.

## Features

- ✅ Easy Azure AD/Entra authentication
- ✅ Auto-create users on first login
- ✅ Group synchronization and role mapping
- ✅ Automatic token refresh for long sessions
- ✅ Custom claims handling
- ✅ State validation for CSRF protection
- ✅ Configurable via environment variables
- ✅ Works across multiple Laravel instances

## Installation

### 1. Install Package

Install via Composer:

```bash
composer require dcplibrary/entra-sso
```

### 2. Environment Variables

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

# Token Refresh
ENTRA_ENABLE_TOKEN_REFRESH=true
ENTRA_REFRESH_THRESHOLD=5

# Custom Claims
ENTRA_STORE_CUSTOM_CLAIMS=false
```

### 3. Run Migrations

The package migrations will run automatically. Just run:
```bash
php artisan migrate
```

### 4. Update User Model

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

### 5. (Optional) Publish Assets

The package works out of the box without publishing anything. However, you can publish assets if you need to customize them:

**Publish config** (only if you need to customize beyond .env variables):
```bash
php artisan vendor:publish --tag=entra-sso-config
```

**Publish views** (only if you need to customize the login view):
```bash
php artisan vendor:publish --tag=entra-sso-views
```

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

## Documentation

See the complete setup guide for:
- [Azure AD configuration](docs/AZURE_SETUP.md)
- [Role mapping setup](docs/ROLE_MAPPING.md)
- [Custom claims configuration](docs/CUSTOM_CLAIMS.md)
- [Token refresh details](docs/TOKEN_REFRESH.md)
- [Troubleshooting](docs/TROUBLESHOOTING.md)

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

## License

MIT
