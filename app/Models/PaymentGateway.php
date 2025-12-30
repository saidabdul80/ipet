<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'display_name',
        'description',
        'credentials',
        'settings',
        'is_active',
        'is_default',
        'currency',
        'supported_channels',
        'webhook_url',
        'callback_url',
        'priority',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'supported_channels' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one default gateway
        static::saving(function ($gateway) {
            if ($gateway->is_default) {
                static::where('id', '!=', $gateway->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Scope to get active gateways
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default gateway
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the gateway's public key
     */
    public function getPublicKey(): ?string
    {
        return $this->credentials['public_key'] ?? null;
    }

    /**
     * Get the gateway's secret key
     */
    public function getSecretKey(): ?string
    {
        return $this->credentials['secret_key'] ?? null;
    }

    /**
     * Check if gateway supports a specific channel
     */
    public function supportsChannel(string $channel): bool
    {
        return in_array($channel, $this->supported_channels ?? []);
    }

    /**
     * Get credential by key
     */
    public function getCredential(string $key): mixed
    {
        return $this->credentials[$key] ?? null;
    }
}

