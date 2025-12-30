<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\Product;
use App\Models\ProductUnit;

class UnitConversionSeeder extends Seeder
{
    public function run(): void
    {
        // Create additional units with conversions
        $piece = Unit::where('short_name', 'pcs')->first();
        
        // Create Carton unit (if not exists)
        $carton = Unit::firstOrCreate(
            ['short_name' => 'ctn'],
            [
                'name' => 'Carton',
                'base_unit_id' => $piece->id,
                'conversion_factor' => 24, // 1 carton = 24 pieces
                'is_active' => true,
            ]
        );

        // Create Pack unit
        $pack = Unit::firstOrCreate(
            ['short_name' => 'pack'],
            [
                'name' => 'Pack',
                'base_unit_id' => $piece->id,
                'conversion_factor' => 6, // 1 pack = 6 pieces
                'is_active' => true,
            ]
        );

        // Create Dozen unit
        $dozen = Unit::firstOrCreate(
            ['short_name' => 'doz'],
            [
                'name' => 'Dozen',
                'base_unit_id' => $piece->id,
                'conversion_factor' => 12, // 1 dozen = 12 pieces
                'is_active' => true,
            ]
        );

        // Create Kilogram and Gram units
        $kg = Unit::where('short_name', 'kg')->first();
        if (!$kg) {
            $kg = Unit::create([
                'name' => 'Kilogram',
                'short_name' => 'kg',
                'base_unit_id' => null,
                'conversion_factor' => 1,
                'is_active' => true,
            ]);
        }

        $gram = Unit::firstOrCreate(
            ['short_name' => 'g'],
            [
                'name' => 'Gram',
                'base_unit_id' => $kg->id,
                'conversion_factor' => 0.001, // 1g = 0.001kg
                'is_active' => true,
            ]
        );

        // Create Liter and Milliliter units
        $liter = Unit::where('short_name', 'L')->first();
        if (!$liter) {
            $liter = Unit::create([
                'name' => 'Liter',
                'short_name' => 'L',
                'base_unit_id' => null,
                'conversion_factor' => 1,
                'is_active' => true,
            ]);
        }

        $ml = Unit::firstOrCreate(
            ['short_name' => 'ml'],
            [
                'name' => 'Milliliter',
                'base_unit_id' => $liter->id,
                'conversion_factor' => 0.001, // 1ml = 0.001L
                'is_active' => true,
            ]
        );

        // Example: Set up multi-unit support for existing products
        $products = Product::limit(5)->get();
        
        foreach ($products as $product) {
            // If product's base unit is piece, add carton and pack options
            if ($product->unit_id == $piece->id) {
                // Add Carton as purchase unit
                ProductUnit::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'unit_id' => $carton->id,
                    ],
                    [
                        'conversion_factor' => 24, // 1 carton = 24 pieces
                        'selling_price' => $product->selling_price * 24 * 0.95, // 5% discount for carton
                        'cost_price' => $product->cost_price * 24,
                        'is_purchase_unit' => true,
                        'is_sale_unit' => true,
                        'is_default' => false,
                    ]
                );

                // Add Pack as sale unit
                ProductUnit::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'unit_id' => $pack->id,
                    ],
                    [
                        'conversion_factor' => 6, // 1 pack = 6 pieces
                        'selling_price' => $product->selling_price * 6 * 0.98, // 2% discount for pack
                        'cost_price' => $product->cost_price * 6,
                        'is_purchase_unit' => false,
                        'is_sale_unit' => true,
                        'is_default' => false,
                    ]
                );
            }
        }

        $this->command->info('Unit conversions seeded successfully!');
        $this->command->info('Units created: Carton (24 pcs), Pack (6 pcs), Dozen (12 pcs)');
        $this->command->info('Example products configured with multi-unit support');
    }
}

