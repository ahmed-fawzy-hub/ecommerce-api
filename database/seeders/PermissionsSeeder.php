<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'create products',
            'create categories',
            'create orders',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerRole->givePermissionTo('create orders');
        
        $deliveryRole = Role::firstOrCreate(['name' => 'delivery']);
        // Assign delivery permissions if any

        // Assign 'customer' role to existing users with type 'customer' who don't have it
        $customers = User::where('type', 'customer')->get();
        foreach($customers as $customer) {
            if(!$customer->hasRole('customer')) {
                $customer->assignRole($customerRole);
            }
        }

        // Assign 'admin' role to existing admin users
        $admins = User::where('type', 'admin')->get();
        foreach($admins as $admin) {
             if(!$admin->hasRole('admin')) {
                $admin->assignRole($adminRole);
            }
        }
    }
}
