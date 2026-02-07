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
        // Create pivot table for user-branch many-to-many relationship
        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'branch_id']);
        });

        // Create pivot table for user-store many-to-many relationship
        Schema::create('store_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'store_id']);
        });

        // Add missing permissions
        $missingPermissions = [
            'update_users', // Add this as alias for edit_users
        ];

        foreach ($missingPermissions as $permission) {
            if (!Permission::where('name', $permission)->where('guard_name', 'sanctum')->exists()) {
                Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
            }
        }

        // Assign new permissions to Super Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo('update_users');
        }

        // Assign to Branch Manager
        $branchManager = Role::where('name', 'Branch Manager')->first();
        if ($branchManager) {
            $branchManager->givePermissionTo(['view_users', 'create_users', 'update_users']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_user');
        Schema::dropIfExists('branch_user');
        
        Permission::where('name', 'update_users')->delete();
    }
};

