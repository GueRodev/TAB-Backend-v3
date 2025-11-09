<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 
        'slug', 
        'description', 
        'parent_id', 
        'level', 
        'order', 
        'is_active'
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
        'order' => 'integer',
    ];

    // Relaciones
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
//Descomentar cuando se implementen los productos
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeProtected($query)
    {
        return $query->where('is_protected', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}