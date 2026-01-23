<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create application roles (super_admin is created by ShieldSeeder)
        Role::firstOrCreate(['name' => 'Product Manager']);
        Role::firstOrCreate(['name' => 'Developer']);
        Role::firstOrCreate(['name' => 'Viewer']);

        // Product Manager gets all permissions (will be granted via Shield UI)
        // Developer and Viewer will have specific permissions assigned via UI

        $this->command->info('Roles created: Product Manager, Developer, Viewer');
    }
}
