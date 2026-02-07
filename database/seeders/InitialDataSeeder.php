<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Store;
use App\Models\Unit;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create Main Branch
        $mainBranch = Branch::firstOrCreate(
            ['code' => 'BR001'],
            [
                'name' => 'Main Branch',
                'email' => 'main@inventory.com',
                'phone' => '+234-800-000-0001',
                'address' => '123 Main Street',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'is_active' => true,
            ]
        );

        // Create Stores for Main Branch
        $mainWarehouse = Store::firstOrCreate(
            ['code' => 'ST001'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'Main Warehouse',
                'type' => 'warehouse',
                'location' => 'Building A, Ground Floor',
                'is_active' => true,
            ]
        );

        $retailStore = Store::firstOrCreate(
            ['code' => 'ST002'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'Retail Store',
                'type' => 'retail_store',
                'location' => 'Building B, First Floor',
                'is_active' => true,
            ]
        );

        // Create Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@inventory.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        if (!$superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole('Super Admin');
        }

        // Create Branch Manager
        $branchManager = User::firstOrCreate(
            ['email' => 'manager@inventory.com'],
            [
                'name' => 'Branch Manager',
                'password' => Hash::make('password'),
                'branch_id' => $mainBranch->id,
                'is_active' => true,
            ]
        );
        if (!$branchManager->hasRole('Branch Manager')) {
            $branchManager->assignRole('Branch Manager');
        }

        // Create Inventory Officer
        $inventoryOfficer = User::firstOrCreate(
            ['email' => 'inventory@inventory.com'],
            [
                'name' => 'Inventory Officer',
                'password' => Hash::make('password'),
                'branch_id' => $mainBranch->id,
                'store_id' => $mainWarehouse->id,
                'is_active' => true,
            ]
        );
        if (!$inventoryOfficer->hasRole('Inventory Officer')) {
            $inventoryOfficer->assignRole('Inventory Officer');
        }

        // Create Cashier
        $cashier = User::firstOrCreate(
            ['email' => 'cashier@inventory.com'],
            [
                'name' => 'Cashier',
                'password' => Hash::make('password'),
                'branch_id' => $mainBranch->id,
                'store_id' => $retailStore->id,
                'is_active' => true,
            ]
        );
        if (!$cashier->hasRole('Cashier')) {
            $cashier->assignRole('Cashier');
        }

        // Create Units of Measurement
        $units = [
            ['name' => 'Piece', 'short_name' => 'pcs', 'base_unit_id' => null, 'conversion_factor' => 1],
            ['name' => 'Carton', 'short_name' => 'ctn', 'base_unit_id' => null, 'conversion_factor' => 1],
            ['name' => 'Kilogram', 'short_name' => 'kg', 'base_unit_id' => null, 'conversion_factor' => 1],
            ['name' => 'Gram', 'short_name' => 'g', 'base_unit_id' => null, 'conversion_factor' => 0.001],
            ['name' => 'Liter', 'short_name' => 'L', 'base_unit_id' => null, 'conversion_factor' => 1],
            ['name' => 'Milliliter', 'short_name' => 'ml', 'base_unit_id' => null, 'conversion_factor' => 0.001],
            ['name' => 'Dozen', 'short_name' => 'doz', 'base_unit_id' => null, 'conversion_factor' => 12],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['short_name' => $unit['short_name']],
                $unit
            );
        }

        // Update base_unit_id for derived units
        $kg = Unit::where('short_name', 'kg')->first();
        if ($kg) {
            Unit::where('short_name', 'g')->update(['base_unit_id' => $kg->id]);
        }

        $liter = Unit::where('short_name', 'L')->first();
        if ($liter) {
            Unit::where('short_name', 'ml')->update(['base_unit_id' => $liter->id]);
        }

        $piece = Unit::where('short_name', 'pcs')->first();
        if ($piece) {
            Unit::where('short_name', 'doz')->update(['base_unit_id' => $piece->id]);
        }

        // Create Product Categories
        $categories = [
            ['code' => 'CAT001', 'name' => 'Electronics', 'parent_id' => null],
            ['code' => 'CAT002', 'name' => 'Clothing', 'parent_id' => null],
            ['code' => 'CAT003', 'name' => 'Food & Beverages', 'parent_id' => null],
            ['code' => 'CAT004', 'name' => 'Home & Garden', 'parent_id' => null],
            ['code' => 'CAT005', 'name' => 'Office Supplies', 'parent_id' => null],
        ];

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(
                ['code' => $category['code']],
                $category
            );
        }

        // Create subcategories
        $electronics = ProductCategory::where('code', 'CAT001')->first();
        if ($electronics) {
            ProductCategory::firstOrCreate(
                ['code' => 'CAT001-01'],
                [
                    'name' => 'Mobile Phones',
                    'parent_id' => $electronics->id,
                ]
            );
            ProductCategory::firstOrCreate(
                ['code' => 'CAT001-02'],
                [
                    'name' => 'Laptops',
                    'parent_id' => $electronics->id,
                ]
            );
        }

        $this->command->info('Initial data seeded successfully!');
        $this->command->info('Super Admin: admin@inventory.com / password');
        $this->command->info('Branch Manager: manager@inventory.com / password');
        $this->command->info('Inventory Officer: inventory@inventory.com / password');
        $this->command->info('Cashier: cashier@inventory.com / password');
    }
}

