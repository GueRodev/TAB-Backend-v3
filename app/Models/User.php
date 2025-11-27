<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles; //  Agregado HasRoles

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ========================================
    // MÉTODOS HELPER PARA ROLES
    // ========================================

    /**
     * Obtener el nombre del rol principal del usuario
     * 
     * @return string
     */
    public function getRoleName(): string
    {
        return $this->roles->first()?->name ?? 'Sin rol';
    }

    /**
     * Verificar si el usuario es Super Admin
     * 
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /**
     * Verificar si el usuario es Cliente
     * 
     * @return bool
     */
    public function isCliente(): bool
    {
        return $this->hasRole('Cliente');
    }

    // ========================================
    // RELACIONES
    // ========================================

    /**
     * Relación con direcciones del usuario (1:N)
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Relación con pedidos del usuario (1:N)
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación con items del carrito del usuario
     */
   // public function cartItems()
    //{
       // return $this->hasMany(CartItem::class);
    //}

    /**
     * Relación con items de la wishlist del usuario
     */
   // public function wishlistItems()
    //{
       // return $this->hasMany(WishlistItem::class);
    //}

    /**
     * Relación con notificaciones del usuario
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}