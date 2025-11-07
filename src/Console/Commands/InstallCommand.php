<?php

namespace Dcplibrary\EntraSSO\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'entra:install
                            {--force : Overwrite existing configuration}
                            {--skip-user-model : Skip User model modifications}
                            {--skip-env : Skip environment variable setup}
                            {--fix-starter-kit : Automatically fix starter kit conflicts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Entra SSO package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║   Entra SSO Installation Wizard       ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        // Step 1: Environment Variables
        if (!$this->option('skip-env')) {
            $this->setupEnvironmentVariables();
        }

        // Step 2: User Model Modifications
        if (!$this->option('skip-user-model')) {
            $this->updateUserModel();
        }

        // Step 2.5: Detect and fix starter kit conflicts
        $this->detectAndFixStarterKits();

        // Step 3: Run Migrations
        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
            $this->info('✓ Migrations completed');
        } else {
            $this->warn('⚠ Remember to run: php artisan migrate');
        }

        // Step 4: Optional Publishing
        $this->newLine();
        $this->info('Optional: Advanced Customization');
        $this->newLine();

        // Only suggest config publishing if they might need it
        $this->line('Config file is only needed for:');
        $this->line('  - Complex group mappings (multiple groups to one role)');
        $this->line('  - Custom claims mapping');
        $this->line('  - Advanced role systems');
        $this->newLine();

        if ($this->confirm('Publish config file for advanced customization?', false)) {
            $this->call('vendor:publish', ['--tag' => 'entra-sso-config']);
            $this->info('✓ Config published to config/entra-sso.php');
        }

        if ($this->confirm('Publish login view for customization?', false)) {
            $this->call('vendor:publish', ['--tag' => 'entra-sso-views']);
            $this->info('✓ Views published to resources/views/vendor/entra-sso/');
        }

        // Success message
        $this->newLine();
        $this->info('════════════════════════════════════════');
        $this->info('✓ Entra SSO Installation Complete!');
        $this->info('════════════════════════════════════════');
        $this->newLine();

        $this->info('Next steps:');
        $this->line('  1. Configure Azure AD application redirect URI:');
        $this->line('     ' . config('app.url') . '/auth/entra/callback');
        $this->line('  2. Add login link: <a href="{{ route(\'entra.login\') }}">Sign in with Microsoft</a>');
        $this->line('  3. Test login at: ' . config('app.url') . '/auth/entra');
        $this->newLine();

        $this->info('After login:');
        $this->line('  - Users will be redirected to: ' . config('app.url') . '/entra/dashboard');
        $this->line('  - The dashboard displays user info, groups, roles, and examples');
        $this->line('  - Customize redirect: Set ENTRA_REDIRECT_AFTER_LOGIN in .env');
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Setup environment variables
     */
    protected function setupEnvironmentVariables()
    {
        $this->info('Step 1: Environment Configuration');
        $this->line('Enter your Azure AD credentials:');
        $this->newLine();

        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        $credentials = [
            'ENTRA_TENANT_ID' => $this->ask('Tenant ID', $this->getEnvValue('ENTRA_TENANT_ID')),
            'ENTRA_CLIENT_ID' => $this->ask('Client ID', $this->getEnvValue('ENTRA_CLIENT_ID')),
            'ENTRA_CLIENT_SECRET' => $this->secret('Client Secret'),
            // Use APP_URL so ports (e.g., :8000) carry through across environments
            'ENTRA_REDIRECT_URI' => $this->ask('Redirect URI', chr(34) . '${APP_URL}/auth/entra/callback' . chr(34)),
        ];

        $defaultConfig = [
            'ENTRA_AUTO_CREATE_USERS' => $this->confirm('Auto-create users on first login?', true) ? 'true' : 'false',
        ];

        // Group sync configuration
        $syncGroups = $this->confirm('Sync Azure AD groups?', true);
        $defaultConfig['ENTRA_SYNC_GROUPS'] = $syncGroups ? 'true' : 'false';

        if ($syncGroups) {
            $defaultConfig['ENTRA_SYNC_ON_LOGIN'] = $this->confirm('Sync groups on every login?', true) ? 'true' : 'false';

            $this->newLine();
            $this->info('Group to Role Mapping');
            $this->line('Map Azure AD groups to application roles.');
            $this->line('Format: Group Name:role,Another Group:admin');
            $this->line('Example: IT Admins:admin,Developers:developer,Staff:user');
            $this->line('Do not include quotes; they will be added automatically.');
            $this->newLine();

            $existingGroupRoles = $this->getEnvValue('ENTRA_GROUP_ROLES');
            $existingGroupRoles = $existingGroupRoles ? trim($existingGroupRoles, "\"'") : null;
            $groupRoles = $this->ask('Group role mappings (or leave empty for none)', $existingGroupRoles);
            if ($groupRoles) {
                $sanitized = trim($groupRoles);
                // Strip surrounding single/double quotes if provided by the user
                $sanitized = trim($sanitized, "\"'");
                $defaultConfig['ENTRA_GROUP_ROLES'] = '"' . $sanitized . '"';
            }
        }

        $defaultConfig['ENTRA_DEFAULT_ROLE'] = $this->ask('Default role for new users', 'user');
        $defaultConfig['ENTRA_ENABLE_TOKEN_REFRESH'] = $this->confirm('Enable automatic token refresh?', true) ? 'true' : 'false';
        $defaultConfig['ENTRA_REFRESH_THRESHOLD'] = $this->ask('Token refresh threshold (minutes)', '5');
        $defaultConfig['ENTRA_STORE_CUSTOM_CLAIMS'] = $this->confirm('Store custom claims?', false) ? 'true' : 'false';
        $defaultConfig['ENTRA_REDIRECT_AFTER_LOGIN'] = $this->ask('Redirect path after login', '/entra/dashboard');

        $allConfig = array_merge($credentials, $defaultConfig);

        foreach ($allConfig as $key => $value) {
            if ($this->getEnvValue($key) && !$this->option('force')) {
                $this->line("  {$key} already exists, skipping...");
                continue;
            }

            if (str_contains($envContent, $key)) {
                // Update existing value
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Append new value
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
        $this->info('✓ Environment variables configured');
        $this->newLine();
    }

    /**
     * Update User model to extend EntraUser
     */
    protected function updateUserModel()
    {
        $this->info('Step 2: Updating User Model');

        $userModelPath = app_path('Models/User.php');

        if (!File::exists($userModelPath)) {
            $this->error('✗ User model not found at app/Models/User.php');
            return;
        }

        // Backup original file
        $backupPath = $userModelPath . '.backup';
        if (!File::exists($backupPath)) {
            File::copy($userModelPath, $backupPath);
            $this->line('  Created backup: app/Models/User.php.backup');
        }

        $content = File::get($userModelPath);
        $modified = false;

        // Check if already using EntraUser
        if (str_contains($content, 'use Dcplibrary\EntraSSO\Models\User as EntraUser')) {
            $this->line('  User model already extends EntraUser');
        } else {
            // Replace Authenticatable import with EntraUser
            $content = preg_replace(
                '/use Illuminate\\\\Foundation\\\\Auth\\\\User as Authenticatable;/',
                'use Dcplibrary\EntraSSO\Models\User as EntraUser;',
                $content
            );

            // Replace class extension
            $content = preg_replace(
                '/class User extends Authenticatable/',
                'class User extends EntraUser',
                $content
            );

            $modified = true;
            $this->info('  ✓ Updated User model to extend EntraUser');
        }

        // Fix casts() method to merge with parent
        if (!str_contains($content, 'array_merge(parent::casts()')) {
            // Pattern to match the casts() method
            $pattern = '/(protected\s+function\s+casts\(\)\s*:\s*array\s*\{[^}]*return\s+)(\[)/s';

            if (preg_match($pattern, $content)) {
                $content = preg_replace(
                    $pattern,
                    '$1array_merge(parent::casts(), $2',
                    $content
                );

                // Also fix the closing bracket
                $content = preg_replace(
                    '/(protected\s+function\s+casts\(\)[^}]*)(];)/s',
                    '$1]);',
                    $content
                );

                $modified = true;
                $this->info('  ✓ Updated casts() method to merge with parent');
            } else {
                $this->warn('  ⚠ Could not automatically update casts() method');
                $this->warn('    Please manually add array_merge(parent::casts(), [...])');
            }
        } else {
            $this->line('  casts() method already merges with parent');
        }

        if ($modified) {
            File::put($userModelPath, $content);
            $this->info('✓ User model updated successfully');
        }

        $this->newLine();
    }

    /**
     * Detect and optionally fix starter kit conflicts
     */
    protected function detectAndFixStarterKits()
    {
        $detectedKits = [];

        // Detect Fortify (Livewire starter kit)
        if (File::exists(config_path('fortify.php'))) {
            $detectedKits[] = 'fortify';
        }

        // Detect Breeze
        if (File::exists(base_path('routes/auth.php'))) {
            $detectedKits[] = 'breeze';
        }

        // Detect Jetstream
        if (File::exists(config_path('jetstream.php'))) {
            $detectedKits[] = 'jetstream';
        }

        if (empty($detectedKits)) {
            return; // No starter kits detected
        }

        $this->newLine();
        $this->warn('⚠ Starter Kit Detected!');
        $this->line('Found: ' . implode(', ', array_map('ucfirst', $detectedKits)));
        $this->newLine();
        $this->line('Starter kits provide authentication features that conflict with Entra SSO:');
        $this->line('  - Email verification (Azure AD verifies emails)');
        $this->line('  - Password management (Azure AD manages passwords)');
        $this->line('  - Two-factor auth (Azure AD provides MFA)');
        $this->newLine();

        if ($this->option('fix-starter-kit') || $this->confirm('Automatically fix starter kit conflicts?', false)) {
            if (in_array('fortify', $detectedKits)) {
                $this->fixFortify();
            }
            if (in_array('breeze', $detectedKits)) {
                $this->fixBreeze();
            }
            if (in_array('jetstream', $detectedKits)) {
                $this->fixJetstream();
            }
        } else {
            $this->warn('Skipping automatic fixes.');
            $this->line('See documentation for manual configuration:');
            $this->line('  https://github.com/dcplibrary/entra-sso#starter-kit-configuration');
        }

        $this->newLine();
    }

    /**
     * Fix Fortify configuration
     */
    protected function fixFortify()
    {
        $this->line('  Fixing Fortify configuration...');

        $fortifyPath = config_path('fortify.php');
        $content = File::get($fortifyPath);

        // Disable views
        if (str_contains($content, "'views' => true")) {
            $content = str_replace("'views' => true", "'views' => false", $content);
            File::put($fortifyPath, $content);
            $this->info('    ✓ Disabled Fortify login views');
        }

        // Remove verified middleware from web routes
        $this->removeVerifiedMiddleware();
    }

    /**
     * Fix Breeze configuration
     */
    protected function fixBreeze()
    {
        $this->line('  Fixing Breeze configuration...');

        $webRoutesPath = base_path('routes/web.php');
        $content = File::get($webRoutesPath);

        // Comment out auth routes
        if (str_contains($content, "require __DIR__.'/auth.php'") &&
            !str_contains($content, "// require __DIR__.'/auth.php'")) {
            $content = str_replace(
                "require __DIR__.'/auth.php'",
                "// require __DIR__.'/auth.php'  // Disabled - using Entra SSO",
                $content
            );
            File::put($webRoutesPath, $content);
            $this->info('    ✓ Disabled Breeze auth routes');
        }

        $this->removeVerifiedMiddleware();
    }

    /**
     * Fix Jetstream configuration
     */
    protected function fixJetstream()
    {
        $this->line('  Fixing Jetstream configuration...');

        $jetstreamPath = config_path('jetstream.php');

        if (File::exists($jetstreamPath)) {
            $content = File::get($jetstreamPath);

            // Comment out features array (simple approach - just warn user)
            $this->warn('    ⚠ Please manually disable Jetstream features in config/jetstream.php');
            $this->line('      See: https://github.com/dcplibrary/entra-sso#laravel-jetstream');
        }

        $this->removeVerifiedMiddleware();
    }

    /**
     * Remove verified middleware from web routes
     */
    protected function removeVerifiedMiddleware()
    {
        $webRoutesPath = base_path('routes/web.php');

        if (!File::exists($webRoutesPath)) {
            return;
        }

        $content = File::get($webRoutesPath);

        // Remove 'verified' middleware
        if (str_contains($content, "'verified'") || str_contains($content, '"verified"')) {
            // Pattern to match middleware arrays
            $patterns = [
                "/middleware\(\['auth',\s*'verified'\]\)/",
                '/middleware\(\["auth",\s*"verified"\]\)/',
                "/middleware\(\['auth',\s*'verified',/",
                '/->middleware\(\'verified\'\)/',
            ];

            $replacements = [
                "middleware(['auth'])",
                'middleware(["auth"])',
                "middleware(['auth',",
                '',
            ];

            $modified = false;
            foreach ($patterns as $index => $pattern) {
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacements[$index], $content);
                    $modified = true;
                }
            }

            if ($modified) {
                File::put($webRoutesPath, $content);
                $this->info('    ✓ Removed verified middleware from routes');
            }
        }
    }

    /**
     * Get environment variable value
     */
    protected function getEnvValue($key)
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return null;
        }

        $envContent = File::get($envPath);

        if (preg_match("/^{$key}=(.*)$/m", $envContent, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
