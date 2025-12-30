<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('paid')->after('status');
            $table->decimal('outstanding_amount', 15, 2)->default(0)->after('amount_paid');
            $table->date('due_date')->nullable()->after('sale_date');
            $table->integer('credit_days')->default(0)->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'outstanding_amount', 'due_date', 'credit_days']);
        });
    }
};

