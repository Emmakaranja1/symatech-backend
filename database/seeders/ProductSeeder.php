<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Samsung 4K Smart TV 55"',
                'title' => 'Samsung 4K Smart TV 55"',
                'sku' => 'TV-001',
                'category' => 'Electronics',
                'price' => 89999.00,
                'cost_price' => 75000.00,
                'stock' => 12,
                'weight' => '18kg',
                'dimensions' => '123x71x6cm',
                'description' => 'Crystal clear 4K UHD display with smart TV capabilities. Features include HDR, built-in streaming apps, and voice control.',
                'image' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=400&h=300&fit=crop',
                'images' => [
                    'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=400&h=300&fit=crop',
                    'https://picsum.photos/seed/tv1/400/300.jpg',
                    'https://picsum.photos/seed/tv2/400/300.jpg'
                ],
                'rating' => 4.5,
                'active' => true,
                'featured' => true,
            ],
            [
                'name' => 'Laptop Pro',
                'title' => 'Laptop Pro',
                'sku' => 'LP-001',
                'category' => 'Electronics',
                'price' => 45000.00,
                'cost_price' => 35000.00,
                'stock' => 15,
                'weight' => '1.5kg',
                'dimensions' => '30x20x5cm',
                'description' => 'High-performance laptop with 16GB RAM, 512GB SSD, and dedicated graphics card. Perfect for professionals and gamers.',
                'image' => 'https://picsum.photos/seed/laptop1/400/300.jpg',
                'images' => [
                    'https://picsum.photos/seed/laptop1/400/300.jpg',
                    'https://picsum.photos/seed/laptop2/400/300.jpg'
                ],
                'rating' => 4.7,
                'active' => true,
                'featured' => true,
            ],
            [
                'name' => 'Coffee Maker Deluxe',
                'title' => 'Coffee Maker Deluxe',
                'sku' => 'CM-001',
                'category' => 'Home & Kitchen',
                'price' => 12000.00,
                'cost_price' => 8000.00,
                'stock' => 8,
                'weight' => '3kg',
                'dimensions' => '25x30x35cm',
                'description' => 'Automatic coffee maker with built-in grinder. Makes espresso, cappuccino, and latte at the touch of a button.',
                'image' => 'https://picsum.photos/seed/coffee1/400/300.jpg',
                'images' => [
                    'https://picsum.photos/seed/coffee1/400/300.jpg',
                    'https://picsum.photos/seed/coffee2/400/300.jpg'
                ],
                'rating' => 4.3,
                'active' => true,
                'featured' => false,
            ],
            [
                'name' => 'Yoga Mat Premium',
                'title' => 'Yoga Mat Premium',
                'sku' => 'YM-001',
                'category' => 'Sports',
                'price' => 3500.00,
                'cost_price' => 2000.00,
                'stock' => 25,
                'weight' => '1.2kg',
                'dimensions' => '183x61x0.6cm',
                'description' => 'Extra thick, non-slip yoga mat with carrying strap. Perfect for all types of yoga and exercise.',
                'image' => 'https://picsum.photos/seed/yoga1/400/300.jpg',
                'images' => [
                    'https://picsum.photos/seed/yoga1/400/300.jpg',
                    'https://picsum.photos/seed/yoga2/400/300.jpg'
                ],
                'rating' => 4.6,
                'active' => true,
                'featured' => false,
            ],
            [
                'name' => 'Office Chair Ergonomic',
                'title' => 'Office Chair Ergonomic',
                'sku' => 'OC-001',
                'category' => 'Furniture',
                'price' => 18000.00,
                'cost_price' => 12000.00,
                'stock' => 3,
                'weight' => '15kg',
                'dimensions' => '65x65x120cm',
                'description' => 'Ergonomic office chair with lumbar support, adjustable height, and breathable mesh back.',
                'image' => 'https://picsum.photos/seed/chair1/400/300.jpg',
                'images' => [
                    'https://picsum.photos/seed/chair1/400/300.jpg',
                    'https://picsum.photos/seed/chair2/400/300.jpg'
                ],
                'rating' => 4.4,
                'active' => true,
                'featured' => false,
            ],
            [
                'name' => 'Winter Jacket',
                'title' => 'Winter Jacket',
                'sku' => 'WJ-001',
                'category' => 'Clothing',
                'price' => 8500.00,
                'cost_price' => 5000.00,
                'stock' => 0,
                'weight' => '0.8kg',
                'dimensions' => '40x30x5cm',
                'description' => 'Warm winter jacket with waterproof exterior and insulated lining. Available in multiple colors.',
                'image' => 'https://picsum.photos/seed/jacket1/400/300.jpg',
                'images' => [
                    'https://picsum.photos/seed/jacket1/400/300.jpg',
                    'https://picsum.photos/seed/jacket2/400/300.jpg'
                ],
                'rating' => 4.2,
                'active' => true,
                'featured' => false,
            ],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create(array_merge($product, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
