<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add unit_id to purchase_order_items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('product_variant_id')->constrained('units')->nullOnDelete();
            $table->decimal('base_quantity', 15, 3)->nullable()->after('quantity')->comment('Quantity in product base unit');
        });

        // Add unit_id to sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('product_variant_id')->constrained('units')->nullOnDelete();
            $table->decimal('base_quantity', 15, 3)->nullable()->after('quantity')->comment('Quantity in product base unit');
        });

        // Add unit_id and base_quantity to stock_ledger
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('product_variant_id')->constrained('units')->nullOnDelete();
            $table->decimal('base_quantity_change', 15, 3)->nullable()->after('quantity')->comment('Quantity change in product base unit');
        });

        // Create product_units pivot table for multi-unit support
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('conversion_factor', 10, 4)->comment('How many base units in this unit for this product');
            $table->decimal('selling_price', 15, 2)->nullable()->comment('Selling price in this unit');
            $table->decimal('cost_price', 15, 2)->nullable()->comment('Cost price in this unit');
            $table->boolean('is_purchase_unit')->default(false)->comment('Can be used for purchasing');
            $table->boolean('is_sale_unit')->default(false)->comment('Can be used for sales');
            $table->boolean('is_default')->default(false)->comment('Default unit for this product');
            $table->timestamps();

            $table->unique(['product_id', 'unit_id']);
        });

        // Add barcode support for different units
        Schema::create('product_unit_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('barcode', 100)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_unit_barcodes');
        Schema::dropIfExists('product_units');
        
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'base_quantity_change']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'base_quantity']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'base_quantity']);
        });
    }
};

