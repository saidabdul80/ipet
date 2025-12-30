<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            
            // Price fields
            $table->decimal('old_cost_price', 15, 2)->nullable();
            $table->decimal('new_cost_price', 15, 2);
            $table->decimal('old_selling_price', 15, 2)->nullable();
            $table->decimal('new_selling_price', 15, 2);
            $table->decimal('old_wholesale_price', 15, 2)->nullable();
            $table->decimal('new_wholesale_price', 15, 2)->nullable();
            $table->decimal('old_retailer_price', 15, 2)->nullable();
            $table->decimal('new_retailer_price', 15, 2)->nullable();
            
            // Profit margin calculations
            $table->decimal('old_profit_margin', 15, 2)->nullable()->comment('Percentage');
            $table->decimal('new_profit_margin', 15, 2)->comment('Percentage');
            $table->decimal('old_profit_amount', 15, 2)->nullable();
            $table->decimal('new_profit_amount', 15, 2);
            
            // Change metadata
            $table->enum('change_type', ['manual_update', 'goods_receipt', 'price_adjustment', 'bulk_update'])->default('manual_update');
            $table->string('change_reason')->nullable();
            $table->string('reference_type')->nullable(); // e.g., 'purchase_order', 'grn'
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamp('changed_at');
            $table->timestamps();
            
            $table->index(['product_id', 'changed_at']);
            $table->index(['product_variant_id', 'changed_at']);
            $table->index(['store_id', 'changed_at']);
            $table->index(['change_type', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_history');
    }
};

