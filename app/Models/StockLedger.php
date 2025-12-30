<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use HasFactory;

    protected $table = 'stock_ledger';

    protected $fillable = [
        'store_id',
        'product_id',
        'product_variant_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'balance_quantity',
        'balance_value',
        'notes',
        'created_by',
        'transaction_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'balance_quantity' => 'decimal:3',
        'balance_value' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForProduct($query, $productId, $variantId = null)
    {
        $query->where('product_id', $productId);
        
        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }
        
        return $query;
    }
}

