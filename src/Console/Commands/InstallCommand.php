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
                            {--skip-env : Skip environment variable setup}';

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
            'ENTRA_REDIRECT_URI' => $this->ask('Redirect URI', config('app.url') . '/auth/entra/callback'),
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
            $this->line('Format: "Group Name:role,Another Group:admin"');
            $this->line('Example: "IT Admins:admin,Developers:developer,Staff:user"');
            $this->newLine();

            $groupRoles = $this->ask('Group role mappings (or leave empty for none)', $this->getEnvValue('ENTRA_GROUP_ROLES'));
            if ($groupRoles) {
                $defaultConfig['ENTRA_GROUP_ROLES'] = '"' . $groupRoles . '"';
            }
        }

        $defaultConfig['ENTRA_DEFAULT_ROLE'] = $this->ask('Default role for new users', 'user');
        $defaultConfig['ENTRA_ENABLE_TOKEN_REFRESH'] = $this->confirm('Enable automatic token refresh?', true) ? 'true' : 'false';
        $defaultConfig['ENTRA_REFRESH_THRESHOLD'] = $this->ask('Token refresh threshold (minutes)', '5');
        $defaultConfig['ENTRA_STORE_CUSTOM_CLAIMS'] = $this->confirm('Store custom claims?', false) ? 'true' : 'false';

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
