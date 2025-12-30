<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Create delete_purchase_orders permission
        $permission = Permission::create(['name' => 'delete_purchase_orders', 'guard_name' => 'web']);

        // Assign to roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $branchManager = Role::where('name', 'Branch Manager')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }

        if ($branchManager) {
            $branchManager->givePermissionTo($permission);
        }

        echo "✓ Added delete_purchase_orders permission\n";
        echo "✓ Assigned to Super Admin and Branch Manager\n";
    }

    public function down(): void
    {
        Permission::where('name', 'delete_purchase_orders')->delete();
    }
};

