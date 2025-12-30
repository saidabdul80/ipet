<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('transaction_type', [
                'receipt', 'issue', 'transfer_in', 'transfer_out', 
                'adjustment_in', 'adjustment_out', 'return', 'damage', 'loss'
            ]);
            $table->string('reference_type')->nullable(); // e.g., 'purchase_order', 'sale', 'transfer'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('balance_quantity', 15, 3);
            $table->decimal('balance_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('transaction_date');
            $table->timestamps();
            
            $table->index(['store_id', 'product_id', 'product_variant_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};

