<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Lego', 'order' => 0],
            ['name' => 'Funkos', 'order' => 1],
            ['name' => 'Anime', 'order' => 2],
            ['name' => 'Peluches', 'order' => 3],
            ['name' => 'Starwars', 'order' => 4],
            ['name' => 'Coleccionables', 'order' => 5],
            ['name' => 'HarryPotter', 'order' => 6],
            [
                'name' => 'Otros', 
                'order' => 7,
                'is_protected' => true,
                'description' => 'Categoría predeterminada para productos sin clasificar'
            ],
        ];
        
        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'] ?? null,
                'level' => 0,
                'order' => $category['order'],
                'is_protected' => $category['is_protected'] ?? false,
                'is_active' => true,
            ]);
        }
    }
}