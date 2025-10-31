# Installation Guide

## Prerequisites

- PHP 8.2 or higher
- Laravel 12
- Composer
- Azure AD tenant with admin access

## Step 1: Add Package to Laravel

### Option A: Local Path (Development)

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

Run:
```bash
composer update dcplibrary/entra-sso
```

### Option B: Git Repository (Production)

```bash
composer require dcplibrary/entra-sso
```

## Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=entra-sso-config
```

This creates `config/entra-sso.php` in your Laravel app.

## Step 3: Configure Environment

Add these variables to your `.env`:

```env
ENTRA_TENANT_ID=your-tenant-id
ENTRA_CLIENT_ID=your-client-id
ENTRA_CLIENT_SECRET=your-client-secret
ENTRA_REDIRECT_URI="${APP_URL}/auth/entra/callback"

# User Management
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

## Step 4: Run Database Migration

Copy the migration file:
```bash
cp vendor/dcplibrary/entra-sso/database/migrations/*_add_entra_fields_to_users.php database/migrations/
```

Run migration:
```bash
php artisan migrate
```

## Step 5: Update User Model

Edit `app/Models/User.php`:

**Replace this line:**
```php
use Illuminate\Foundation\Auth\User as Authenticatable;
```

**With:**
```php
use VENDOR_NAME\EntraSSO\Models\User as EntraUser;
```

**Replace this line:**
```php
class User extends Authenticatable
```

**With:**
```php
class User extends EntraUser
```

**Your User model should now look like:**
```php
<?php

namespace App\Models;

use VENDOR_NAME\EntraSSO\Models\User as EntraUser;
use Illuminate\Notifications\Notifiable;

class User extends EntraUser
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'entra_id',
        'role',
        'entra_groups',
        'entra_custom_claims',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

The base `EntraUser` class provides:
- Automatic casting for `entra_groups` and `entra_custom_claims`
- Helper methods like `hasRole()`, `inGroup()`, `isAdmin()`
- Custom claim accessors

## Step 6: Configure Role Mapping

Edit `config/entra-sso.php`:

```php
'group_role_mapping' => [
    'IT Admins' => 'admin',
    'Developers' => 'developer',
    'Managers' => 'manager',
],
```

## Step 7: Create Login View

Create `resources/views/auth/login.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <a href="{{ route('entra.login') }}">Sign in with Microsoft</a>
</body>
</html>
```

## Step 8: Test

Start your Laravel app:
```bash
php artisan serve
```

Visit: http://localhost:8000/auth/entra

You should be redirected to Microsoft login!

## Next Steps

- Configure Azure AD application (see AZURE_SETUP.md)
- Set up custom claims (see CUSTOM_CLAIMS.md)
- Configure role-based access control
