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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('Inventory Management System');
            $table->string('app_short_name')->nullable();
            $table->text('app_description')->nullable();
            
            // Logo and Images
            $table->string('logo_path')->nullable();
            $table->string('logo_dark_path')->nullable(); // For dark mode
            $table->string('favicon_path')->nullable();
            $table->string('login_background_path')->nullable();
            $table->string('login_side_image_path')->nullable();
            
            // Color Scheme
            $table->string('primary_color')->default('#1976D2');
            $table->string('secondary_color')->default('#424242');
            $table->string('accent_color')->default('#82B1FF');
            $table->string('error_color')->default('#FF5252');
            $table->string('info_color')->default('#2196F3');
            $table->string('success_color')->default('#4CAF50');
            $table->string('warning_color')->default('#FB8C00');
            
            // Login Page Settings
            $table->enum('login_layout', ['centered', 'split', 'background'])->default('split');
            $table->string('login_title')->nullable();
            $table->text('login_subtitle')->nullable();
            $table->boolean('show_logo_on_login')->default(true);
            
            // Additional Settings
            $table->string('currency_symbol')->default('₦');
            $table->string('currency_code')->default('NGN');
            $table->string('timezone')->default('Africa/Lagos');
            $table->string('date_format')->default('Y-m-d');
            $table->string('time_format')->default('H:i:s');
            
            // Contact Information
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_website')->nullable();
            
            // Social Media
            $table->json('social_links')->nullable();
            
            // System Settings
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            
            $table->timestamps();
        });

        // Insert default settings
        DB::table('app_settings')->insert([
            'app_name' => 'Inventory Management System',
            'app_short_name' => 'IMS',
            'primary_color' => '#1976D2',
            'secondary_color' => '#424242',
            'login_layout' => 'split',
            'show_logo_on_login' => true,
            'currency_symbol' => '₦',
            'currency_code' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};

