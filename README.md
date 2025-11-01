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

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=entra-sso-config
```

### 3. Environment Variables

Add to your `.env` file:

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

### 4. Run Migrations

The package migrations will run automatically. Just run:
```bash
php artisan migrate
```

### 5. Update User Model

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

### 6. (Optional) Publish Login View

The package includes a default login view. You only need to publish it if you want to customize it:

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

If you're developing this package locally and want to test changes in a Laravel application, use a path repository:

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

4. Make changes in the `entra-sso` directory, then refresh in your Laravel app:
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
