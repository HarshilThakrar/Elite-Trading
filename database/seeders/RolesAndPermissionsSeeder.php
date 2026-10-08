<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles
        $roles = [
            'Super Admin',
            'Admin',
            'Sales Team',
            'Purchase Team',
            'Accounts Team',
            'Warehouse Team'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create Super Admin User
        $user = User::firstOrCreate([
            'email' => 'admin@demoerp.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('test123'),
        ]);

        $user->assignRole('Super Admin');
    }
}
