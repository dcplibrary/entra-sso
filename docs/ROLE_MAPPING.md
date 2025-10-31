# Role Mapping Setup

This package provides flexible role mapping that can map Microsoft Entra (Azure AD) groups to application roles. It supports both simple string-based roles and relationship-based role systems (like Spatie Permission).

## How It Works

When a user logs in via Entra SSO:

1. The package fetches the user's groups from Microsoft Graph API
2. It compares the group names/IDs against the `group_role_mapping` configuration
3. The first matching group determines the user's role
4. If no match is found, the `default_role` is assigned

## Configuration

### Basic Setup

Edit your `config/entra-sso.php` file:

```php
return [
    // Enable group synchronization
    'sync_groups' => env('ENTRA_SYNC_GROUPS', true),

    // Sync groups on every login (or just on user creation)
    'sync_on_login' => env('ENTRA_SYNC_ON_LOGIN', true),

    // Map Entra groups to application roles
    'group_role_mapping' => [
        'IT Administrators' => 'admin',
        'Computer Services' => 'admin',
        'Developers' => 'developer',
        'HR Team' => 'hr',
        'Support Staff' => 'support',
    ],

    // Default role if no group matches
    'default_role' => env('ENTRA_DEFAULT_ROLE', 'user'),

    // Optional: Use a role model (for packages like Spatie Permission)
    'role_model' => env('ENTRA_ROLE_MODEL', null),
];
```

### Environment Variables

You can also configure via `.env`:

```env
ENTRA_SYNC_GROUPS=true
ENTRA_SYNC_ON_LOGIN=true
ENTRA_DEFAULT_ROLE=user
```

## Role Assignment Methods

### Method 1: String-Based Roles (Default)

The simplest approach - roles are stored as a string in the `role` field:

**Migration:**
```php
$table->string('role')->nullable();
```

**Usage:**
```php
// Check user role
if ($user->role === 'admin') {
    // Admin logic
}

// In Blade
@if(auth()->user()->role === 'admin')
    <a href="/admin">Admin Panel</a>
@endif
```

### Method 2: Relationship-Based Roles

For advanced role management systems like Spatie Permission:

**Configuration:**
```php
// config/entra-sso.php
'role_model' => \Spatie\Permission\Models\Role::class,
```

**User Model:**
```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    // ... rest of your model
}
```

**Usage:**
```php
// Check user role
if ($user->hasRole('admin')) {
    // Admin logic
}

// Check multiple roles
if ($user->hasAnyRole(['admin', 'developer'])) {
    // Logic for admins or developers
}
```

## Mapping by Group ID

Instead of group names, you can also map by Group Object ID (recommended for production):

```php
'group_role_mapping' => [
    // By group name (may change)
    'IT Administrators' => 'admin',

    // By group ID (stable identifier)
    '12345678-1234-1234-1234-123456789abc' => 'admin',
],
```

**To find Group IDs:**

1. Go to Azure Portal → Entra ID → Groups
2. Click on the group
3. Copy the "Object ID"

## Protecting Routes with Roles

Use the `entra.role` middleware to protect routes:

```php
// Single role
Route::middleware(['auth', 'entra.role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// Multiple roles (user must have one of these)
Route::middleware(['auth', 'entra.role:admin,developer'])->group(function () {
    Route::get('/tools', [ToolsController::class, 'index']);
});
```

## Protecting Routes by Entra Groups

You can also protect routes based on Entra group membership directly:

```php
Route::middleware(['auth', 'entra.group:IT Administrators'])->group(function () {
    Route::get('/servers', [ServerController::class, 'index']);
});

// Multiple groups
Route::middleware(['auth', 'entra.group:IT Administrators,Developers'])->group(function () {
    Route::get('/debug', [DebugController::class, 'index']);
});
```

## Role Priority

When a user belongs to multiple groups that map to different roles, the **first match** in the mapping array wins:

```php
'group_role_mapping' => [
    'IT Administrators' => 'admin',      // Checked first
    'Developers' => 'developer',         // Checked second
    'All Staff' => 'user',               // Checked third
],
```

If a user is in both "IT Administrators" and "Developers", they will get the `admin` role.

## Sync Timing

### Sync on Login (Default)
```php
'sync_on_login' => true,
```
Groups and roles are updated every time the user logs in.

### Sync on Creation Only
```php
'sync_on_login' => false,
```
Groups and roles are only set when the user account is first created. Subsequent logins won't update their role.

## Advanced: Custom Role Logic

If you need custom role assignment logic, you can extend the `EntraSSOController`:

```php
namespace App\Http\Controllers;

use Dcplibrary\EntraSSO\Http\Controllers\EntraSSOController as BaseController;

class CustomEntraSSOController extends BaseController
{
    protected function mapGroupsToRole($groups)
    {
        // Custom logic here
        $groupNames = array_column($groups, 'displayName');

        // Example: Admin if in both groups
        if (in_array('IT Team', $groupNames) && in_array('Security Team', $groupNames)) {
            return 'super_admin';
        }

        // Fall back to default mapping
        return parent::mapGroupsToRole($groups);
    }
}
```

Then update your routes to use the custom controller.

## Checking Group Membership in Code

The user's groups are stored in the `entra_groups` JSON field:

```php
// Check if user is in a specific group
if (in_array('IT Administrators', auth()->user()->entra_groups ?? [])) {
    // User is in IT Administrators group
}

// Get all groups
$groups = auth()->user()->entra_groups ?? [];
```

## Troubleshooting

### Groups not syncing

1. Ensure `sync_groups` is `true` in config
2. Check that your Azure app has `GroupMember.Read.All` permission
3. Verify the permission is granted admin consent
4. Check Laravel logs for sync errors

### Wrong role assigned

1. Check the order of your `group_role_mapping` (first match wins)
2. Verify the group name matches exactly (case-sensitive)
3. Consider using Group IDs instead of names
4. Check that the user is actually in the expected group in Azure

### Role not persisting

1. Ensure `role` is in the `$fillable` array in your User model
2. Check that the migration added the `role` column
3. Verify `sync_on_login` is set to your desired behavior
