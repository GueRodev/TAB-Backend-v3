<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'brand',
        'description',
        'price',
        'cost_price',
        'stock',
        'stock_min',
        'sku',
        'image_url',
        'category_id',
        'original_category_id',
        'status',
        'is_featured'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'stock' => 'integer',
        'stock_min' => 'integer',
    ];

    // Relaciones
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function originalCategory()
    {
        return $this->belongsTo(Category::class, 'original_category_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('brand', 'like', "%{$term}%");
        });
    }

    // Scopes adicionales

    /**
     * Scope para obtener productos con stock bajo
     * Filtra productos donde: stock <= stock_min Y stock > 0
     * Uso: Product::lowStock()->get()
     *
     * Nota: Comentado por ahora. Se agregará cuando se implemente
     * el módulo de alertas de stock o reporte de inventario.
     */
    // public function scopeLowStock($query)
    // {
    //     return $query->whereRaw('stock <= stock_min')->where('stock', '>', 0);
    // }
}