# Azure AD Setup Guide

## Step 1: Access Azure Portal

1. Go to https://portal.azure.com
2. Navigate to **Azure Active Directory** (or **Microsoft Entra ID**)

## Step 2: Register Application

1. Click **App registrations** > **+ New registration**
2. Fill in:
   - **Name**: Your App Name
   - **Supported account types**: Single tenant (recommended)
   - **Redirect URI**: Web - `https://yourdomain.com/auth/entra/callback`
3. Click **Register**

## Step 3: Copy Application Details

From the Overview page, copy:
- **Application (client) ID** → `ENTRA_CLIENT_ID`
- **Directory (tenant) ID** → `ENTRA_TENANT_ID`

## Step 4: Create Client Secret

1. Go to **Certificates & secrets**
2. Click **+ New client secret**
3. Add description and choose expiration
4. Click **Add**
5. **IMMEDIATELY COPY THE VALUE** → `ENTRA_CLIENT_SECRET`
   ⚠️ You can only see this once!

## Step 5: Configure API Permissions

1. Go to **API permissions**
2. Click **+ Add a permission**
3. Select **Microsoft Graph** > **Delegated permissions**
4. Add these permissions:
   - ✅ openid
   - ✅ profile
   - ✅ email
   - ✅ User.Read
   - ✅ offline_access (for token refresh)
   - ✅ GroupMember.Read.All (for group sync)
5. Click **Add permissions**
6. Click **Grant admin consent for [Your Organization]**

## Step 6: Configure Authentication

1. Go to **Authentication**
2. Under **Implicit grant and hybrid flows**:
   - ✅ Check **ID tokens**
3. Click **Save**

## Step 7: Add Multiple Redirect URIs (Optional)

Add URLs for different environments:
- Production: `https://yourdomain.com/auth/entra/callback`
- Staging: `https://staging.yourdomain.com/auth/entra/callback`
- Local: `http://localhost:8000/auth/entra/callback`

## Finding Group Names/IDs

To map groups to roles, you need group names or IDs:

1. Go to **Azure Active Directory** > **Groups**
2. Click on a group
3. Copy the **Object ID** (group ID) or **Name**
4. Use in `config/entra-sso.php`:

```php
'group_role_mapping' => [
    'IT Admins' => 'admin',  // By name
    'abc-123-def' => 'admin', // By ID
],
```

## Testing

After setup, test by visiting:
`http://localhost:8000/auth/entra`

You should be redirected to Microsoft login!
