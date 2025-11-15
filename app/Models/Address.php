<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'province',
        'canton',
        'district',
        'address_details',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ========================================
    // RELACIONES
    // ========================================

    /**
     * Relación con usuario (N:1)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Solo direcciones del usuario especificado
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Dirección predeterminada
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ========================================
    // MÉTODOS HELPER
    // ========================================

    /**
     * Obtener dirección completa formateada
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->province}, {$this->canton}, {$this->district}. {$this->address_details}";
    }

    /**
     * Convertir a formato para order_shipping_addresses
     */
    public function toShippingSnapshot(): array
    {
        return [
            'province' => $this->province,
            'canton' => $this->canton,
            'district' => $this->district,
            'address_details' => $this->address_details,
        ];
    }
}
