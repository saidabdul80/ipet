<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'category',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'user_id',
        'credit_limit',
        'wallet_balance',
        'is_active',
        'meta_data',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function customerPricing(): HasMany
    {
        return $this->hasMany(CustomerPricing::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRegistered($query)
    {
        return $query->where('type', 'registered');
    }

    public function scopeWalkIn($query)
    {
        return $query->where('type', 'walk_in');
    }

    public function scopeWholesaler($query)
    {
        return $query->where('category', 'wholesaler');
    }

    public function scopeRetailer($query)
    {
        return $query->where('category', 'retailer');
    }

    // generateCode() method
    public static function generateCode()
    {
        // Get the last customer ordered by code (not created_at) to avoid duplicates
        $lastCustomer = Customer::orderBy('code', 'desc')->first();

        if ($lastCustomer && preg_match('/CUST(\d+)/', $lastCustomer->code, $matches)) {
            $lastNumber = (int) $matches[1];
        } else {
            $lastNumber = 0;
        }

        // Keep trying until we find a unique code
        $attempts = 0;
        do {
            $newNumber = $lastNumber + 1 + $attempts;
            $code = 'CUST' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
            $exists = Customer::where('code', $code)->exists();
            $attempts++;
        } while ($exists && $attempts < 100);

        return $code;
    }

    public function getSpecialPrice($productId, $variantId = null)
    {
        $query = $this->customerPricing()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            });

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        return $query->first();
    }
}

