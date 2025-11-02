# Laravel Starter Kit Configuration

This guide explains how to configure Laravel starter kits (Breeze, Jetstream, Livewire, Inertia) to work with Entra SSO.

## Table of Contents

- [Understanding Starter Kit Conflicts](#understanding-starter-kit-conflicts)
- [Recommended Installation Approaches](#recommended-installation-approaches)
- [Automatic Configuration](#automatic-configuration)
- [Manual Configuration](#manual-configuration)
  - [React/Vue Starter Kits (Inertia + Breeze)](#reactvue-starter-kits-inertia--breeze)
  - [Livewire Starter Kit (Fortify)](#livewire-starter-kit-fortify)
  - [Laravel Breeze](#laravel-breeze)
  - [Laravel Jetstream](#laravel-jetstream)
- [Why These Changes Are Needed](#why-these-changes-are-needed)

## Understanding Starter Kit Conflicts

Laravel starter kits provide authentication features that **conflict** with Entra SSO:

- **Competing login routes** (`/login`)
- **Email verification** (redundant - Azure AD verifies emails)
- **Password management** (redundant - Azure AD manages passwords)
- **Two-factor authentication** (redundant - Azure AD provides MFA)

**All starter kits from `laravel new` include authentication:**

- **React** → Installs Laravel Breeze with Inertia + React
- **Vue** → Installs Laravel Breeze with Inertia + Vue
- **Livewire** → Installs Livewire with Fortify authentication

## Recommended Installation Approaches

### Option 1: None (No starter kit) - Recommended

```bash
laravel new my-app
# When prompted: Select "None"
cd my-app
composer require dcplibrary/entra-sso
php artisan entra:install
```

This is the cleanest approach with zero conflicts. You can still use React/Vue/Livewire by installing them separately without auth.

### Option 2: Starter kit with auto-fix

```bash
laravel new my-app
# Select React, Vue, or Livewire
cd my-app
composer require dcplibrary/entra-sso
php artisan entra:install --fix-starter-kit
```

The install command will detect and fix authentication conflicts automatically.

### Option 3: Manual installation

If you already have a starter kit installed, see the manual configuration sections below.

## Automatic Configuration

The `entra:install` command automatically detects Breeze/Jetstream/Fortify and offers to fix conflicts.

**Run the wizard:**
```bash
php artisan entra:install
```

**Non-interactive mode:**
```bash
php artisan entra:install --fix-starter-kit
```

The wizard will:
- Detect which starter kit you're using
- Show you what changes need to be made
- Offer to make the changes automatically
- Create backups before modifying files

## Manual Configuration

If you prefer to configure manually or need to understand what changes are needed:

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

## Why These Changes Are Needed

Azure AD/Entra SSO already provides:

- ✅ **User authentication** - No need for password-based login
- ✅ **Email verification** - Microsoft verifies all emails
- ✅ **Password management** - Handled by Azure AD
- ✅ **Two-factor authentication** - Azure AD provides MFA
- ✅ **Password reset** - Managed through Azure AD portal
- ✅ **Profile management** - Managed through Azure AD

Starter kits provide these same features, creating conflicts and redundancy.

## Framework Compatibility Matrix

| Starter Kit | Compatible | Conflicts | Auto-Fix Available |
|-------------|------------|-----------|-------------------|
| **None (Blade only)** | ✅ Yes | None | N/A |
| **React (Inertia)** | ⚠️ Conflicts | Breeze auth | ✅ Yes |
| **Vue (Inertia)** | ⚠️ Conflicts | Breeze auth | ✅ Yes |
| **Livewire** | ⚠️ Conflicts | Fortify auth | ✅ Yes |
| **Laravel Breeze** | ⚠️ Conflicts | Auth routes | ✅ Yes |
| **Laravel Jetstream** | ⚠️ Conflicts | Fortify/Jetstream | ✅ Yes |

## Mixing Entra SSO with Password Auth

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

## Troubleshooting

### Routes still conflict after configuration

1. Clear route cache:
   ```bash
   php artisan route:clear
   php artisan route:list | grep login
   ```

2. Verify auth routes are not being loaded:
   ```php
   // In routes/web.php - ensure this is commented out
   // require __DIR__.'/auth.php';
   ```

### Email verification middleware still blocking users

1. Check all route definitions for `'verified'` middleware
2. Search your codebase:
   ```bash
   grep -r "middleware.*verified" routes/
   ```

### Users being redirected to wrong login page

1. Check that your `/login` route redirects to Entra:
   ```bash
   php artisan route:list | grep login
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

## Getting Help

If you encounter issues with starter kit configuration:

1. Run `php artisan entra:install --fix-starter-kit` to auto-detect and fix
2. Check Laravel logs: `storage/logs/laravel.log`
3. Review route list: `php artisan route:list`
4. Open an issue: https://github.com/dcplibrary/entra-sso/issues
