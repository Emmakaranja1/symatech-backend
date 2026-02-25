<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductWithImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Apple iPhone 15 Pro',
                'category' => 'Electronics',
                'price' => 159999.99,
                'stock' => 8,
                'description' => 'The most powerful iPhone ever with A17 Pro chip, titanium design, and revolutionary camera system.',
                'image' => 'https://images.unsplash.com/photo-1696446701796-da61225697cc?w=400&h=300&fit=crop',
                'rating' => 4.8,
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'category' => 'Electronics',
                'price' => 149999.99,
                'stock' => 12,
                'description' => 'Premium Android smartphone with S Pen, advanced camera system, and AI features.',
                'image' => 'https://images.unsplash.com/photo-1610945415295-d9bbf067e597?w=400&h=300&fit=crop',
                'rating' => 4.7,
            ],
            [
                'name' => 'MacBook Pro 16"',
                'category' => 'Electronics',
                'price' => 299999.99,
                'stock' => 5,
                'description' => 'Powerful laptop with M3 Pro chip, stunning Liquid Retina XDR display, and all-day battery life.',
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=300&fit=crop',
                'rating' => 4.9,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
