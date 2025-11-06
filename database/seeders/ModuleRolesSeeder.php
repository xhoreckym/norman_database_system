<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ModuleRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder adds module-specific roles for access control to:
     * - Empodat Suspect module
     * - Literature module
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create module-specific roles
        $moduleRoles = [
            'empodat_suspect',
            'literature',
        ];

        foreach ($moduleRoles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
            $this->command->info("Role '{$roleName}' created or already exists.");
        }

        $this->command->info('Module roles seeder completed successfully.');
    }
}

// php artisan db:seed --class=ModuleRolesSeeder
