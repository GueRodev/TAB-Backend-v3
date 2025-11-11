<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'order_type',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_option',
        'payment_method',
        'subtotal',
        'shipping_cost',
        'total',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderShippingAddress::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Scopes
    public function scopeOnline($query)
    {
        return $query->where('order_type', 'online');
    }

    public function scopeInStore($query)
    {
        return $query->where('order_type', 'in_store');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeDelivery($query)
    {
        return $query->where('delivery_option', 'delivery');
    }

    public function scopePickup($query)
    {
        return $query->where('delivery_option', 'pickup');
    }

    // Métodos auxiliares
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isOnline(): bool
    {
        return $this->order_type === 'online';
    }

    public function isInStore(): bool
    {
        return $this->order_type === 'in_store';
    }

    public function requiresShipping(): bool
    {
        return $this->delivery_option === 'delivery';
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function canBeArchived(): bool
    {
        return $this->status === 'completed';
    }
}
