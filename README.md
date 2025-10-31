# Entra SSO Package for Laravel 12

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

### 1. Add to Laravel Project

Add to your Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./entra-sso"
        }
    ],
    "require": {
        "dcplibrary/entra-sso": "*"
    }
}
```

Then run:
```bash
composer update
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

### 4. Run Migration

Copy the migration file to your Laravel app:
```bash
cp entra-sso/database/migrations/*_add_entra_fields_to_users.php database/migrations/
php artisan migrate
```

### 5. Update User Model

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

protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'entra_groups' => 'array',
        'entra_custom_claims' => 'array',
    ];
}
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
- Azure AD configuration
- Role mapping setup
- Custom claims configuration
- Token refresh details
- Troubleshooting

## License

MIT
