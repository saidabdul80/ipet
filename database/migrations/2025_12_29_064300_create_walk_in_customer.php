<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create walk-in customer if it doesn't exist
        $walkInCustomer = Customer::where('code', 'CUST00000')->first();
        
        if (!$walkInCustomer) {
            Customer::create([
                'code' => 'CUST00000',
                'name' => 'Walk-in Customer',
                'email' => 'walkin@system.local',
                'phone' => '0000000000',
                'address' => 'N/A',
                'city' => 'N/A',
                'state' => 'N/A',
                'country' => 'N/A',
                'type' => 'walk_in',
                'category' => 'general',
                'credit_limit' => 0,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete walk-in customer
        Customer::where('code', 'CUST00000')->delete();
    }
};

