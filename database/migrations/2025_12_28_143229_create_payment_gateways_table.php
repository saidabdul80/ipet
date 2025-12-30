<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Paystack, Monnify, PalmPay
            $table->string('driver'); // paystack, monnify, palmpay
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->text('credentials'); // Encrypted credentials (stored as encrypted string)
            $table->json('settings')->nullable(); // Additional settings
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->string('currency')->default('NGN');
            $table->json('supported_channels')->nullable(); // card, bank, ussd, etc.
            $table->string('webhook_url')->nullable();
            $table->string('callback_url')->nullable();
            $table->integer('priority')->default(0); // Display order
            $table->timestamps();

            // Ensure only one default gateway
            $table->unique(['driver']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
