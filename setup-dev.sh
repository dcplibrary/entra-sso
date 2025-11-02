#!/bin/bash

# Laravel Entra SSO Development Setup Script
# This script sets up an existing Laravel app to use the Entra SSO package for local development

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Laravel Entra SSO Setup (Dev Mode)  ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

# Check if we're in a Laravel directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: Not in a Laravel directory!${NC}"
    echo "Please run this script from your Laravel project root."
    exit 1
fi

echo -e "${GREEN}✓ Laravel project detected${NC}"
echo ""

# Prompt for package location
read -p "Enter path to entra-sso package (default: ../entra-sso): " package_path
package_path=${package_path:-"../entra-sso"}

if [ ! -d "$package_path" ]; then
    echo -e "${RED}Error: Package directory not found at ${package_path}${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Package found${NC}"
echo ""

# Get vendor name from package composer.json
vendor_name=$(grep -o '"name": "[^"]*"' ${package_path}/composer.json | head -1 | cut -d'"' -f4)

echo -e "${BLUE}Step 1: Adding package to composer.json${NC}"

# Ensure minimum-stability is set to dev
if grep -q "\"minimum-stability\"" composer.json; then
    if [ "$(uname)" = "Darwin" ]; then
        sed -i '' 's/"minimum-stability": "[^"]*"/"minimum-stability": "dev"/' composer.json
    else
        sed -i 's/"minimum-stability": "[^"]*"/"minimum-stability": "dev"/' composer.json
    fi
    echo -e "${GREEN}✓ Minimum stability updated to dev${NC}"
else
    if [ "$(uname)" = "Darwin" ]; then
        sed -i '' '/"name":/a\
    "minimum-stability": "dev",
' composer.json
    else
        sed -i '/"name":/a\    "minimum-stability": "dev",' composer.json
    fi
    echo -e "${GREEN}✓ Minimum stability set to dev${NC}"
fi

# Check if repository already exists
if grep -q "\"type\": \"path\"" composer.json 2>/dev/null; then
    echo -e "${YELLOW}Repository already exists in composer.json${NC}"
else
    # Add repository to composer.json
    if [ "$(uname)" == "Darwin" ]; then
        # macOS
        sed -i '' '/"require": {/i\
    "repositories": [\
        {\
            "type": "path",\
            "url": "'${package_path}'"\
        }\
    ],
' composer.json
    else
        # Linux
        sed -i '/"require": {/i\    "repositories": [\n        {\n            "type": "path",\n            "url": "'${package_path}'"\n        }\n    ],' composer.json
    fi
    echo -e "${GREEN}✓ Repository added${NC}"
fi

# Add package to require section
if grep -q "${vendor_name}" composer.json; then
    echo -e "${YELLOW}Package already in composer.json${NC}"
else
    # Add to require
    if [ "$(uname)" == "Darwin" ]; then
        sed -i '' 's|\"require\": {|\"require\": {\
        \"'${vendor_name}'\": \"*\",|' composer.json
    else
        sed -i 's|\"require\": {|\"require\": {\\n        \"'${vendor_name}'\": \"*\",|' composer.json
    fi
    echo -e "${GREEN}✓ Package added to require${NC}"
fi

echo ""
echo -e "${BLUE}Step 2: Installing package${NC}"
composer update ${vendor_name}
echo -e "${GREEN}✓ Package installed${NC}"

echo ""
echo -e "${BLUE}Step 3: Updating User model${NC}"

# Backup User model
if [ ! -f "app/Models/User.php.backup" ]; then
    cp app/Models/User.php app/Models/User.php.backup
    echo -e "${GREEN}✓ User model backed up${NC}"
fi

# Check if already using EntraUser
if grep -q "use Dcplibrary\\\\EntraSSO\\\\Models\\\\User" app/Models/User.php; then
    echo -e "${YELLOW}User model already extends EntraUser${NC}"
else
    # Replace the Authenticatable import
    if [ "$(uname)" == "Darwin" ]; then
        sed -i '' "s/use Illuminate\\\\Foundation\\\\Auth\\\\User as Authenticatable;/use Dcplibrary\\\\EntraSSO\\\\Models\\\\User as EntraUser;/" app/Models/User.php
    else
        sed -i "s/use Illuminate\\\\Foundation\\\\Auth\\\\User as Authenticatable;/use Dcplibrary\\\\EntraSSO\\\\Models\\\\User as EntraUser;/" app/Models/User.php
    fi

    # Replace the class extension
    if [ "$(uname)" == "Darwin" ]; then
        sed -i '' "s/class User extends Authenticatable/class User extends EntraUser/" app/Models/User.php
    else
        sed -i "s/class User extends Authenticatable/class User extends EntraUser/" app/Models/User.php
    fi

    echo -e "${GREEN}✓ User model updated to extend EntraUser${NC}"
fi

# Fix casts() method to merge with parent
if grep -q "array_merge(parent::casts()" app/Models/User.php; then
    echo -e "${YELLOW}casts() method already merges with parent${NC}"
else
    echo -e "${YELLOW}⚠ IMPORTANT: Update casts() method in app/Models/User.php${NC}"
    echo -e "${YELLOW}  Change from:${NC}"
    echo -e "${YELLOW}    return [${NC}"
    echo -e "${YELLOW}  To:${NC}"
    echo -e "${YELLOW}    return array_merge(parent::casts(), [${NC}"
    echo ""
    echo -e "${YELLOW}  This is REQUIRED for Entra SSO to work correctly!${NC}"
fi

echo ""
echo -e "${BLUE}Step 4: Environment configuration${NC}"

# Prompt for Azure credentials
echo ""
echo -e "${YELLOW}Please enter your Azure AD credentials:${NC}"
read -p "Tenant ID: " tenant_id
read -p "Client ID: " client_id
read -p "Client Secret: " client_secret

# Prompt for group role mappings
echo ""
read -p "Enter group role mappings (optional, format: \"Group Name:role,Another:admin\"): " group_roles

# Add to .env if not already present
if grep -q "ENTRA_TENANT_ID" .env; then
    echo -e "${YELLOW}Entra configuration already in .env${NC}"
    echo -e "${YELLOW}Please update manually if needed${NC}"
else
    cat >> .env << ENV_EOF

# Entra SSO Configuration
ENTRA_TENANT_ID=${tenant_id}
ENTRA_CLIENT_ID=${client_id}
ENTRA_CLIENT_SECRET=${client_secret}
ENTRA_REDIRECT_URI="\${APP_URL}/auth/entra/callback"
ENTRA_AUTO_CREATE_USERS=true
ENTRA_SYNC_GROUPS=true
ENTRA_SYNC_ON_LOGIN=true
ENV_EOF

    # Add group roles if provided
    if [ -n "$group_roles" ]; then
        echo "ENTRA_GROUP_ROLES=\"${group_roles}\"" >> .env
    fi

    cat >> .env << ENV_EOF
ENTRA_DEFAULT_ROLE=user
ENTRA_ENABLE_TOKEN_REFRESH=true
ENTRA_REFRESH_THRESHOLD=5
ENTRA_STORE_CUSTOM_CLAIMS=false
ENTRA_REDIRECT_AFTER_LOGIN=/entra/dashboard
ENV_EOF
    echo -e "${GREEN}✓ Environment variables added${NC}"
fi

echo ""
echo -e "${BLUE}Step 5: Running migrations${NC}"
echo -e "${YELLOW}Note: Package migrations run automatically${NC}"
read -p "Run migrations now? (y/n): " run_migrations

if [ "$run_migrations" = "y" ] || [ "$run_migrations" = "Y" ]; then
    php artisan migrate
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${YELLOW}⚠ Remember to run: php artisan migrate${NC}"
fi

echo ""
echo -e "${BLUE}Step 6: Creating example routes${NC}"

# Add routes if not already present
if grep -q "route('entra.login')" routes/web.php 2>/dev/null; then
    echo -e "${YELLOW}Routes already configured${NC}"
else
    cat >> routes/web.php << 'ROUTES_EOF'

// SSO Routes (auth routes provided by package automatically)
// Login page (optional - package includes default login view)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Dashboard (protected)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
ROUTES_EOF
    echo -e "${GREEN}✓ Routes added${NC}"
fi

echo ""
echo -e "${BLUE}Optional: Publish package assets${NC}"
read -p "Publish config for customization? (y/n): " publish_config
if [ "$publish_config" = "y" ] || [ "$publish_config" = "Y" ]; then
    php artisan vendor:publish --tag=entra-sso-config
    echo -e "${GREEN}✓ Config published to config/entra-sso.php${NC}"
fi

read -p "Publish login view for customization? (y/n): " publish_views
if [ "$publish_views" = "y" ] || [ "$publish_views" = "Y" ]; then
    php artisan vendor:publish --tag=entra-sso-views
    echo -e "${GREEN}✓ Views published to resources/views/vendor/entra-sso/${NC}"
fi

echo ""
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ Setup Complete!${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo ""
echo -e "${RED}⚠ CRITICAL: Don't forget to fix casts() method!${NC}"
echo -e "${YELLOW}Edit app/Models/User.php and change the casts() method:${NC}"
echo ""
echo -e "${YELLOW}protected function casts(): array${NC}"
echo -e "${YELLOW}{${NC}"
echo -e "${YELLOW}    return array_merge(parent::casts(), [  // ← Add array_merge${NC}"
echo -e "${YELLOW}        'email_verified_at' => 'datetime',${NC}"
echo -e "${YELLOW}        'password' => 'hashed',${NC}"
echo -e "${YELLOW}    ]);${NC}"
echo -e "${YELLOW}}${NC}"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo "  1. Fix casts() method in app/Models/User.php (REQUIRED)"
echo "  2. Configure Azure AD application redirect URIs"
echo "  3. Update role mappings in config/entra-sso.php (if published)"
echo "  4. Start Laravel: ${YELLOW}php artisan serve${NC}"
echo "  5. Visit: ${YELLOW}http://localhost:8000/login${NC}"
echo ""
echo -e "${BLUE}Package Features:${NC}"
echo "  ✓ Migrations run automatically from package"
echo "  ✓ Config works via .env (publishing optional)"
echo "  ✓ Default login view included (publishing optional)"
echo "  ✓ Fillable fields auto-merged from parent User model"
echo ""
echo -e "${BLUE}Documentation:${NC}"
echo "  - README: ${package_path}/README.md"
echo "  - Azure Setup: ${package_path}/docs/AZURE_SETUP.md"
echo "  - Custom Claims: ${package_path}/docs/CUSTOM_CLAIMS.md"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"
