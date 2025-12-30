<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'product_price_history';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'store_id',
        'old_cost_price',
        'new_cost_price',
        'old_selling_price',
        'new_selling_price',
        'old_wholesale_price',
        'new_wholesale_price',
        'old_retailer_price',
        'new_retailer_price',
        'old_profit_margin',
        'new_profit_margin',
        'old_profit_amount',
        'new_profit_amount',
        'change_type',
        'change_reason',
        'reference_type',
        'reference_id',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'old_cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
        'old_selling_price' => 'decimal:2',
        'new_selling_price' => 'decimal:2',
        'old_wholesale_price' => 'decimal:2',
        'new_wholesale_price' => 'decimal:2',
        'old_retailer_price' => 'decimal:2',
        'new_retailer_price' => 'decimal:2',
        'old_profit_margin' => 'decimal:2',
        'new_profit_margin' => 'decimal:2',
        'old_profit_amount' => 'decimal:2',
        'new_profit_amount' => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope to filter by product
     */
    public function scopeForProduct($query, int $productId, ?int $variantId = null)
    {
        $query->where('product_id', $productId);
        
        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }
        
        return $query;
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('changed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by change type
     */
    public function scopeChangeType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Calculate profit margin percentage
     */
    public static function calculateProfitMargin(float $costPrice, float $sellingPrice): float
    {
        if ($sellingPrice <= 0) {
            return 0;
        }
        
        $profit = $sellingPrice - $costPrice;
        return round(($profit / $sellingPrice) * 100, 2);
    }

    /**
     * Calculate profit amount
     */
    public static function calculateProfitAmount(float $costPrice, float $sellingPrice): float
    {
        return round($sellingPrice - $costPrice, 2);
    }
}

