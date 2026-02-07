<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Create debtor permissions for sanctum guard
        $permissions = [
            'view_debtors',
            'manage_debtors',
            'record_debtor_payments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Assign to Super Admin role
        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'sanctum')->first();
        if ($superAdminRole) {
            foreach ($permissions as $permission) {
                $perm = Permission::where('name', $permission)->where('guard_name', 'sanctum')->first();
                if ($perm && !$superAdminRole->hasPermissionTo($perm)) {
                    $superAdminRole->givePermissionTo($perm);
                }
            }
        }

        // Assign view and record payment to Branch Manager role
        $managerRole = Role::where('name', 'Branch Manager')->where('guard_name', 'sanctum')->first();
        if ($managerRole) {
            $managerPermissions = ['view_debtors', 'record_debtor_payments'];
            foreach ($managerPermissions as $permission) {
                $perm = Permission::where('name', $permission)->where('guard_name', 'sanctum')->first();
                if ($perm && !$managerRole->hasPermissionTo($perm)) {
                    $managerRole->givePermissionTo($perm);
                }
            }
        }
    }

    public function down(): void
    {
        $permissions = ['view_debtors', 'manage_debtors', 'record_debtor_payments'];
        
        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
    }
};

