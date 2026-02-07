<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add supplier permissions
        $supplierPermissions = [
            'view_suppliers',
            'create_suppliers',
            'edit_suppliers',
            'delete_suppliers',
        ];

        foreach ($supplierPermissions as $permission) {
            if (!Permission::where('name', $permission)->where('guard_name', 'sanctum')->exists()) {
                Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
            }
        }

        // Assign supplier permissions to roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($supplierPermissions);
        }

        $branchManager = Role::where('name', 'Branch Manager')->first();
        if ($branchManager) {
            $branchManager->givePermissionTo([
                'view_suppliers',
                'create_suppliers',
                'edit_suppliers',
            ]);
        }

        $inventoryOfficer = Role::where('name', 'Inventory Officer')->first();
        if ($inventoryOfficer) {
            $inventoryOfficer->givePermissionTo([
                'view_suppliers',
                'create_suppliers',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove supplier permissions
        $supplierPermissions = [
            'view_suppliers',
            'create_suppliers',
            'edit_suppliers',
            'delete_suppliers',
        ];

        foreach ($supplierPermissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
    }
};

