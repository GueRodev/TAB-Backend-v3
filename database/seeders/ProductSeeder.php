<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');
        
        $products = [
            // Lego
            ['name' => 'LEGO Star Wars Millennium Falcon', 'category' => 'Lego', 'brand' => 'LEGO', 'price' => 149999.99, 'stock' => 5, 'featured' => true],
            ['name' => 'LEGO City Fire Station', 'category' => 'Lego', 'brand' => 'LEGO', 'price' => 79999.99, 'stock' => 10],
            ['name' => 'LEGO Harry Potter Hogwarts Castle', 'category' => 'Lego', 'brand' => 'LEGO', 'price' => 199999.99, 'stock' => 3, 'featured' => true],
            
            // Funkos
            ['name' => 'Funko Pop Darth Vader', 'category' => 'Funkos', 'brand' => 'Funko', 'price' => 12999.99, 'stock' => 20, 'featured' => true],
            ['name' => 'Funko Pop Spider-Man', 'category' => 'Funkos', 'brand' => 'Funko', 'price' => 11999.99, 'stock' => 15],
            
            // Anime
            ['name' => 'Figura Goku Ultra Instinct', 'category' => 'Anime', 'brand' => 'Bandai', 'price' => 45999.99, 'stock' => 8],
            ['name' => 'Nendoroid Naruto', 'category' => 'Anime', 'brand' => 'Good Smile', 'price' => 35999.99, 'stock' => 12],
            
            // Peluches
            ['name' => 'Peluche Pikachu Gigante', 'category' => 'Peluches', 'brand' => 'Pokemon', 'price' => 29999.99, 'stock' => 7],
            ['name' => 'Peluche Baby Yoda', 'category' => 'Peluches', 'brand' => 'Disney', 'price' => 24999.99, 'stock' => 10],
            
            // Starwars
            ['name' => 'Sable de Luz Luke Skywalker', 'category' => 'Starwars', 'brand' => 'Hasbro', 'price' => 89999.99, 'stock' => 4, 'featured' => true],
            
            // Coleccionables
            ['name' => 'Hot Toys Iron Man Mark 85', 'category' => 'Coleccionables', 'brand' => 'Hot Toys', 'price' => 349999.99, 'stock' => 2, 'featured' => true],
            ['name' => 'Estatua Batman The Dark Knight', 'category' => 'Coleccionables', 'brand' => 'Sideshow', 'price' => 499999.99, 'stock' => 1],
            
            // HarryPotter
            ['name' => 'Varita Mágica Hermione', 'category' => 'HarryPotter', 'brand' => 'Noble Collection', 'price' => 39999.99, 'stock' => 15],
            ['name' => 'Capa de Invisibilidad Réplica', 'category' => 'HarryPotter', 'brand' => 'WOW Stuff', 'price' => 54999.99, 'stock' => 6],
            
            // Otros
            ['name' => 'Producto Sin Categoría', 'category' => 'Otros', 'brand' => 'Genérico', 'price' => 9999.99, 'stock' => 100],
        ];
        
        //Crear productos
        foreach ($products as $item) {
            Product::create([
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'category_id' => $categories[$item['category']]->id,
                'brand' => $item['brand'],
                'description' => 'Descripción del producto ' . $item['name'],
                'price' => $item['price'],
                'stock' => $item['stock'],
                'sku' => 'SKU-' . strtoupper(Str::random(8)),
                'status' => $item['stock'] > 0 ? 'active' : 'out_of_stock',
                'is_featured' => $item['featured'] ?? false,
            ]);
        }
    }
}