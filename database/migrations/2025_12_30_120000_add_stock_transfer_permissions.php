<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Create stock transfer permissions for both guards
        $permissions = [
            'view_stock_transfers',
            'create_stock_transfers',
            'approve_stock_transfers',
            'receive_stock_transfers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Assign to Super Admin role
        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        if ($superAdminRole) {
            foreach ($permissions as $permission) {
                $perm = Permission::where('name', $permission)->where('guard_name', 'web')->first();
                if ($perm && !$superAdminRole->hasPermissionTo($perm)) {
                    $superAdminRole->givePermissionTo($perm);
                }
            }
        }

        // Assign to Branch Manager role
        $branchManagerRole = Role::where('name', 'Branch Manager')->where('guard_name', 'web')->first();
        if ($branchManagerRole) {
            $managerPermissions = ['view_stock_transfers', 'create_stock_transfers', 'approve_stock_transfers', 'receive_stock_transfers'];
            foreach ($managerPermissions as $permission) {
                $perm = Permission::where('name', $permission)->where('guard_name', 'web')->first();
                if ($perm && !$branchManagerRole->hasPermissionTo($perm)) {
                    $branchManagerRole->givePermissionTo($perm);
                }
            }
        }

        // Assign to Inventory Officer role
        $inventoryOfficerRole = Role::where('name', 'Inventory Officer')->where('guard_name', 'web')->first();
        if ($inventoryOfficerRole) {
            $officerPermissions = ['view_stock_transfers', 'create_stock_transfers', 'receive_stock_transfers'];
            foreach ($officerPermissions as $permission) {
                $perm = Permission::where('name', $permission)->where('guard_name', 'web')->first();
                if ($perm && !$inventoryOfficerRole->hasPermissionTo($perm)) {
                    $inventoryOfficerRole->givePermissionTo($perm);
                }
            }
        }

        echo "✓ Added stock transfer permissions\n";
        echo "✓ Assigned to Super Admin, Branch Manager, and Inventory Officer\n";
    }

    public function down(): void
    {
        $permissions = [
            'view_stock_transfers',
            'create_stock_transfers',
            'approve_stock_transfers',
            'receive_stock_transfers',
        ];

        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
    }
};

