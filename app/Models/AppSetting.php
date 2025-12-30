<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'app_name',
        'app_short_name',
        'app_description',
        'logo_path',
        'logo_dark_path',
        'favicon_path',
        'login_background_path',
        'login_side_image_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'error_color',
        'info_color',
        'success_color',
        'warning_color',
        'login_layout',
        'login_title',
        'login_subtitle',
        'show_logo_on_login',
        'currency_symbol',
        'currency_code',
        'timezone',
        'date_format',
        'time_format',
        'company_email',
        'company_phone',
        'company_address',
        'company_website',
        'social_links',
        'maintenance_mode',
        'maintenance_message',
    ];

    protected $casts = [
        'show_logo_on_login' => 'boolean',
        'maintenance_mode' => 'boolean',
        'social_links' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are updated
        static::saved(function () {
            Cache::forget('app_settings');
        });

        static::deleted(function () {
            Cache::forget('app_settings');
        });
    }

    /**
     * Get the singleton instance of app settings
     */
    public static function getInstance()
    {
        return Cache::remember('app_settings', 3600, function () {
            return static::first() ?? static::create([
                'app_name' => 'Inventory Management System',
                'primary_color' => '#1976D2',
            ]);
        });
    }

    /**
     * Get a specific setting value
     */
    public static function get($key, $default = null)
    {
        $settings = static::getInstance();
        return $settings->$key ?? $default;
    }

    /**
     * Set a specific setting value
     */
    public static function set($key, $value)
    {
        $settings = static::getInstance();
        $settings->$key = $value;
        $settings->save();
        return $settings;
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    /**
     * Get dark logo URL
     */
    public function getLogoDarkUrlAttribute()
    {
        return $this->logo_dark_path ? asset('storage/' . $this->logo_dark_path) : null;
    }

    /**
     * Get favicon URL
     */
    public function getFaviconUrlAttribute()
    {
        return $this->favicon_path ? asset('storage/' . $this->favicon_path) : null;
    }

    /**
     * Get login background URL
     */
    public function getLoginBackgroundUrlAttribute()
    {
        return $this->login_background_path ? asset('storage/' . $this->login_background_path) : null;
    }

    /**
     * Get login side image URL
     */
    public function getLoginSideImageUrlAttribute()
    {
        return $this->login_side_image_path ? asset('storage/' . $this->login_side_image_path) : null;
    }

    /**
     * Get color scheme as array
     */
    public function getColorSchemeAttribute()
    {
        return [
            'primary' => $this->primary_color,
            'secondary' => $this->secondary_color,
            'accent' => $this->accent_color,
            'error' => $this->error_color,
            'info' => $this->info_color,
            'success' => $this->success_color,
            'warning' => $this->warning_color,
        ];
    }
}

