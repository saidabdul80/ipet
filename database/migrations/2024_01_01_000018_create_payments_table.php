<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->string('payable_type'); // Order, Sale, etc.
            $table->unsignedBigInteger('payable_id');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('payment_method', ['cash', 'wallet', 'bank_transfer', 'card', 'mixed'])->default('cash');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'verified', 'failed', 'reversed'])->default('pending');
            $table->string('reference')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('proof_of_payment')->nullable(); // File path for bank transfer proof
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('payment_date');
            $table->timestamps();
            
            $table->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

