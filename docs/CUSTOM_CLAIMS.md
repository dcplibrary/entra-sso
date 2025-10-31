# Custom Claims Configuration

Custom claims allow you to include additional user attributes from Azure AD in your Laravel app.

## Available Standard Claims

These are automatically included:
- `name` - Full name
- `email` - Email address
- `oid` - Object ID
- `preferred_username` - Username

## Common Additional Claims

- `jobTitle` - Job title
- `department` - Department
- `officeLocation` - Office location
- `mobilePhone` - Mobile phone
- `employeeId` - Employee ID

## Step 1: Configure in Azure AD

### Method 1: Token Configuration UI

1. Go to **App registrations** > Your App
2. Click **Token configuration**
3. Click **+ Add optional claim**
4. Select **ID** token type
5. Check desired claims:
   - jobTitle
   - department
   - officeLocation
   - etc.
6. Click **Add**

### Method 2: Edit Manifest

1. Go to **Manifest**
2. Find `optionalClaims` section
3. Add:

```json
{
  "optionalClaims": {
    "idToken": [
      {
        "name": "jobTitle",
        "source": null,
        "essential": false
      },
      {
        "name": "department",
        "source": null,
        "essential": false
      }
    ]
  }
}
```

4. Click **Save**

## Step 2: Configure Laravel

Edit `config/entra-sso.php`:

```php
'custom_claims_mapping' => [
    'jobTitle' => 'job_title',
    'department' => 'department',
    'officeLocation' => 'office',
    'mobilePhone' => 'phone',
],

'store_custom_claims' => true, // Store all claims as JSON
```

## Step 3: Add Database Columns

Create migration:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('job_title')->nullable();
    $table->string('department')->nullable();
    $table->string('office')->nullable();
    $table->string('phone')->nullable();
});
```

## Step 4: Update User Model

```php
protected $fillable = [
    'name',
    'email',
    'entra_id',
    'role',
    'entra_groups',
    'entra_custom_claims',
    'job_title',
    'department',
    'office',
    'phone',
];
```

## Using Custom Claims

### Access Mapped Claims

```php
$user = auth()->user();
$jobTitle = $user->job_title;
$department = $user->department;
```

### Access JSON Stored Claims

```php
$customClaim = $user->entra_custom_claims['some_claim'] ?? null;
```

### In Views

```blade
<p>Job Title: {{ auth()->user()->job_title }}</p>
<p>Department: {{ auth()->user()->department }}</p>
```

## Extension Attributes

For truly custom fields not in Azure AD by default:

1. These appear as: `extension_[appId]_customField`
2. Map them like any other claim:

```php
'custom_claims_mapping' => [
    'extension_abc123_employeeId' => 'employee_id',
],
```

## Testing

Add a test route:

```php
Route::get('/test-claims', function () {
    return auth()->user()->entra_custom_claims;
})->middleware('auth');
```

Login and visit `/test-claims` to see all available claims.
