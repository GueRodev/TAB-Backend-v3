<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShippingAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'province',
        'canton',
        'district',
        'address_details',
    ];

    // Relación
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Método auxiliar
    public function getFullAddress(): string
    {
        return "{$this->province}, {$this->canton}, {$this->district} - {$this->address_details}";
    }
}
