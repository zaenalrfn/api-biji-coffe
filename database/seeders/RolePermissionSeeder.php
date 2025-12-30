<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::firstOrCreate(['name' => 'create-product']);
        Permission::firstOrCreate(['name' => 'edit-product']);
        Permission::firstOrCreate(['name' => 'delete-product']);

        // Create other permissions if needed, but for now focusing on products

        // Create Roles and assign permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo(Permission::all());

        $roleUser = Role::firstOrCreate(['name' => 'user']);
        // User gets NO product management permissions

        $roleDriver = Role::firstOrCreate(['name' => 'driver']);
        // Driver specific permissions can be added later

        // If there are other routes that 'user' needs access to that are currently gated, 
        // we might need to check. But assuming standard auth covers them.
    }
}
