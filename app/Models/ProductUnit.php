<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_factor',
        'selling_price',
        'cost_price',
        'is_purchase_unit',
        'is_sale_unit',
        'is_default',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'selling_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_purchase_unit' => 'boolean',
        'is_sale_unit' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Convert quantity to base unit
     */
    public function toBaseQuantity(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert quantity from base unit
     */
    public function fromBaseQuantity(float $baseQuantity): float
    {
        return $baseQuantity / $this->conversion_factor;
    }
}

