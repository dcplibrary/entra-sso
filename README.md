[![Dependabot Updates](https://github.com/dcplibrary/entra-sso/actions/workflows/dependabot/dependabot-updates/badge.svg)](https://github.com/dcplibrary/entra-sso/actions/workflows/dependabot/dependabot-updates) [![Semantic-Release](https://github.com/dcplibrary/entra-sso/actions/workflows/semantic-release.yml/badge.svg)](https://github.com/dcplibrary/entra-sso/actions/workflows/semantic-release.yml)

# Entra SSO for Laravel

A simple, reusable Microsoft Entra (Azure AD) Single Sign-On package for Laravel 12+ with automatic user creation, role mapping, group sync, token refresh, and custom claims support.

## Table of Contents

- [Features](#features)
- [Quick Start](#quick-start)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Documentation](#documentation)
- [Troubleshooting](#troubleshooting)
- [Development](#development)
- [License](#license)

## Features

- ✅ **Simple Setup** - Interactive installation wizard handles everything
- ✅ **Azure AD/Entra Authentication** - Secure OAuth2/OIDC implementation
- ✅ **Auto-Create Users** - Users created automatically on first login
- ✅ **Role Mapping** - Map Azure AD groups to application roles
- ✅ **Group Sync** - Sync user groups from Azure AD
- ✅ **Token Refresh** - Automatic token refresh for long sessions
- ✅ **Custom Claims** - Extract additional user attributes from Azure AD
- ✅ **Built-in Dashboard** - Default landing page with user info and examples
- ✅ **Framework Agnostic** - Works with Blade, Livewire, Inertia (React/Vue)
- ✅ **Starter Kit Support** - Auto-configuration for Breeze/Jetstream/Fortify

## Quick Start

```bash
# 1. Install package
composer require dcplibrary/entra-sso

# 2. Run interactive wizard
php artisan entra:install

# 3. Add login button to your views
<a href="{{ route('entra.login') }}">Sign in with Microsoft</a>
```

That's it! The wizard handles Azure AD configuration, environment setup, User model updates, and migrations.

## Requirements

- **PHP** 8.2 or higher
- **Laravel** 12.0 or higher
- **Azure AD tenant** with admin access ([Setup Guide](docs/AZURE_SETUP.md))
- Any session driver (database, redis, file, etc.)

### Compatibility

Works with all Laravel frontend stacks and starter kits:

| Stack | Status | Notes |
|-------|--------|-------|
| **Blade** | ✅ Full support | Zero conflicts |
| **Livewire** | ✅ Auto-configurable | Use `--fix-starter-kit` flag |
| **Inertia (Vue/React)** | ✅ Auto-configurable | Use `--fix-starter-kit` flag |
| **Breeze/Jetstream** | ✅ Auto-configurable | Use `--fix-starter-kit` flag |

**Starter kit conflicts?** The `entra:install --fix-starter-kit` command automatically detects and resolves authentication conflicts. See [Starter Kit Configuration](docs/STARTER_KITS.md) for details.

## Installation

### Interactive Installation (Recommended)

```bash
composer require dcplibrary/entra-sso
php artisan entra:install
```

The wizard will guide you through:
1. Azure AD credentials setup
2. Environment configuration
3. User model updates
4. Database migrations
5. Starter kit conflict resolution (if needed)

**Command options:**
```bash
# Auto-fix starter kit conflicts
php artisan entra:install --fix-starter-kit

# Skip specific steps
php artisan entra:install --skip-user-model
php artisan entra:install --skip-env
```

### Manual Installation

For manual installation steps, see the [Installation Guide](docs/INSTALLATION.md).

## Usage

### Basic Usage

**Add login button:**
```blade
<a href="{{ route('entra.login') }}">Sign in with Microsoft</a>
```

**Protect routes:**
```php
// By role
Route::middleware(['auth', 'entra.role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// By Azure AD group
Route::middleware(['auth', 'entra.group:IT Admins'])->group(function () {
    Route::get('/servers', [ServerController::class, 'index']);
});
```

**Use helper methods:**
```php
$user = auth()->user();

// Check roles
$user->hasRole('admin');
$user->isAdmin();

// Check groups
$user->inGroup('IT Admins');
$user->getEntraGroups();

// Get custom claims
$user->getCustomClaim('department');
```

### Configuration

**Redirect after login:**
```env
ENTRA_REDIRECT_AFTER_LOGIN=/dashboard
```

**Map groups to roles:**
```env
ENTRA_GROUP_ROLES="IT Admins:admin,Developers:developer,Staff:user"
```

**Enable token refresh:**
```env
ENTRA_ENABLE_TOKEN_REFRESH=true
```

For more usage examples, see the default dashboard at `/entra/dashboard` after logging in.

## Documentation

Comprehensive guides for all features:

- **[Installation Guide](docs/INSTALLATION.md)** - Detailed installation instructions
- **[Azure AD Setup](docs/AZURE_SETUP.md)** - Configure your Azure AD application
- **[Starter Kit Configuration](docs/STARTER_KITS.md)** - Configure Breeze/Jetstream/Livewire/Inertia
- **[Role Mapping](docs/ROLE_MAPPING.md)** - Map Azure AD groups to application roles
- **[Custom Claims](docs/CUSTOM_CLAIMS.md)** - Extract additional user attributes
- **[Token Refresh](docs/TOKEN_REFRESH.md)** - Automatic token refresh for long sessions
- **[Troubleshooting](docs/TROUBLESHOOTING.md)** - Common issues and solutions

## Troubleshooting

**Common issues:**
- Login redirects not working → Check session driver and `APP_URL`
- "Invalid state parameter" → Clear session cache
- User not created → Enable `ENTRA_AUTO_CREATE_USERS=true`
- Groups not syncing → Check `GroupMember.Read.All` permission in Azure
- Token refresh failures → User needs to log in again

**📖 Full troubleshooting guide:** [Troubleshooting Guide](docs/TROUBLESHOOTING.md)

**Need help?** [Open an issue on GitHub](https://github.com/dcplibrary/entra-sso/issues)

## Development

### Local Package Development

For local development, add a path repository to your Laravel app's `composer.json`:

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

### Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

MIT
