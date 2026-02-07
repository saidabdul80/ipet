<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Branch Management
            'view_branches',
            'create_branches',
            'edit_branches',
            'delete_branches',
            
            // Store Management
            'view_stores',
            'create_stores',
            'edit_stores',
            'delete_stores',
            
            // Product Management
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'manage_product_pricing',
            
            // Inventory Management
            'view_inventory',
            'receive_stock',
            'adjust_stock',
            'transfer_stock',
            // 'approve_stock_transfers',
            
            // Purchase Orders
            'view_purchase_orders',
            'create_purchase_orders',
            'edit_purchase_orders',
            'approve_purchase_orders',
            'receive_purchase_orders',
            
            // Sales & POS
            'view_sales',
            'create_sales',
            'void_sales',
            'apply_discounts',
            'override_prices',
            
            // Orders
            'view_orders',
            'create_orders',
            'edit_orders',
            'confirm_orders',
            'cancel_orders',
            
            // Customer Management
            'view_customers',
            'create_customers',
            'edit_customers',
            'delete_customers',
            'manage_customer_pricing',
            
            // Wallet Management
            'view_wallet_transactions',
            'fund_wallet',
            'approve_wallet_funding',
            'reverse_wallet_transactions',
            
            // Payments
            'view_payments',
            'receive_payments',
            'verify_payments',
            
            // Reports
            'view_reports',
            'view_branch_reports',
            'view_enterprise_reports',
            'export_reports',
            
            // User Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'assign_roles',
            
            // Audit Logs
            'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create roles and assign permissions

        // Super Admin - Full access
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'sanctum']);
        $superAdmin->syncPermissions(Permission::all());

        // Branch Manager - Branch-level access
        $branchManager = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'sanctum']);
        $branchManager->syncPermissions([
            'view_stores', 'create_stores', 'edit_stores',
            'view_products', 'create_products', 'edit_products', 'manage_product_pricing',
            'view_inventory', 'receive_stock', 'adjust_stock', 'transfer_stock',
            'view_purchase_orders', 'create_purchase_orders', 'edit_purchase_orders', 'approve_purchase_orders',
            'view_sales', 'create_sales', 'apply_discounts',
            'view_orders', 'create_orders', 'edit_orders', 'confirm_orders',
            'view_customers', 'create_customers', 'edit_customers', 'manage_customer_pricing',
            'view_wallet_transactions', 'approve_wallet_funding',
            'view_payments', 'receive_payments', 'verify_payments',
            'view_branch_reports', 'export_reports',
            'view_users', 'create_users', 'edit_users',
            'view_audit_logs',
        ]);

        // Inventory Officer
        $inventoryOfficer = Role::firstOrCreate(['name' => 'Inventory Officer', 'guard_name' => 'sanctum']);
        $inventoryOfficer->syncPermissions([
            'view_products',
            'view_inventory', 'receive_stock', 'adjust_stock', 'transfer_stock',
            'view_purchase_orders', 'create_purchase_orders', 'receive_purchase_orders',
        ]);

        // Cashier/Sales Staff
        $cashier = Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'sanctum']);
        $cashier->syncPermissions([
            'view_products',
            'view_inventory',
            'view_sales', 'create_sales',
            'view_orders', 'create_orders',
            'view_customers',
            'view_payments', 'receive_payments',
        ]);

        // Accounts Officer
        $accountsOfficer = Role::firstOrCreate(['name' => 'Accounts Officer', 'guard_name' => 'sanctum']);
        $accountsOfficer->syncPermissions([
            'view_sales',
            'view_orders',
            'view_purchase_orders',
            'view_wallet_transactions', 'approve_wallet_funding', 'reverse_wallet_transactions',
            'view_payments', 'verify_payments',
            'view_reports', 'view_branch_reports', 'export_reports',
            'view_audit_logs',
        ]);

        // Customer (Portal Access)
        $customer = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'sanctum']);
        $customer->syncPermissions([
            'view_products',
            'create_orders',
            'view_wallet_transactions',
        ]);
    }
}

