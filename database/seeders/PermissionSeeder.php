<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'view_dashboard', 'display_name' => 'View Dashboard', 'group' => 'dashboard', 'description' => 'Access the main dashboard'],

            // Reports
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'group' => 'reports', 'description' => 'View generated reports'],
            ['name' => 'create_reports', 'display_name' => 'Create Reports', 'group' => 'reports', 'description' => 'Create new reports'],
            ['name' => 'schedule_reports', 'display_name' => 'Schedule Reports', 'group' => 'reports', 'description' => 'Schedule automated reports'],
            ['name' => 'export_data', 'display_name' => 'Export Data', 'group' => 'reports', 'description' => 'Export data to CSV/Excel'],

            // Campaigns
            ['name' => 'view_campaigns', 'display_name' => 'View Campaigns', 'group' => 'campaigns', 'description' => 'View campaign data'],
            ['name' => 'edit_campaigns', 'display_name' => 'Edit Campaigns', 'group' => 'campaigns', 'description' => 'Edit campaign settings and metadata'],

            // Accounts
            ['name' => 'view_accounts', 'display_name' => 'View Accounts', 'group' => 'accounts', 'description' => 'View ad accounts'],
            ['name' => 'edit_accounts', 'display_name' => 'Edit Accounts', 'group' => 'accounts', 'description' => 'Edit ad account settings'],
            ['name' => 'sync_accounts', 'display_name' => 'Sync Accounts', 'group' => 'accounts', 'description' => 'Trigger account data sync'],

            // Integrations
            ['name' => 'view_integrations', 'display_name' => 'View Integrations', 'group' => 'integrations', 'description' => 'View connected integrations'],
            ['name' => 'manage_integrations', 'display_name' => 'Manage Integrations', 'group' => 'integrations', 'description' => 'Connect and disconnect integrations'],

            // Users
            ['name' => 'view_users', 'display_name' => 'View Users', 'group' => 'users', 'description' => 'View team members'],
            ['name' => 'invite_users', 'display_name' => 'Invite Users', 'group' => 'users', 'description' => 'Invite new team members'],
            ['name' => 'manage_users', 'display_name' => 'Manage Users', 'group' => 'users', 'description' => 'Edit and remove team members'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'group' => 'users', 'description' => 'Create and edit custom roles'],

            // Alerts
            ['name' => 'view_alerts', 'display_name' => 'View Alerts', 'group' => 'alerts', 'description' => 'View alert notifications'],
            ['name' => 'manage_alerts', 'display_name' => 'Manage Alerts', 'group' => 'alerts', 'description' => 'Create and edit alerts'],

            // Settings
            ['name' => 'view_settings', 'display_name' => 'View Settings', 'group' => 'settings', 'description' => 'View tenant settings'],
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings', 'group' => 'settings', 'description' => 'Edit tenant settings'],

            // Benchmarks
            ['name' => 'view_benchmarks', 'display_name' => 'View Benchmarks', 'group' => 'benchmarks', 'description' => 'View industry benchmarks'],
            ['name' => 'calculate_benchmarks', 'display_name' => 'Calculate Benchmarks', 'group' => 'benchmarks', 'description' => 'Trigger benchmark calculations'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
