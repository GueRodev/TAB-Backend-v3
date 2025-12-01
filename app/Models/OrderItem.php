<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_description',
        'product_image_url',
        'quantity',
        'price_at_purchase',
        'cost_price_at_purchase',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_at_purchase' => 'decimal:2',
        'cost_price_at_purchase' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relaciones
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Métodos auxiliares
    public function calculateSubtotal(): float
    {
        return $this->quantity * $this->price_at_purchase;
    }
}
