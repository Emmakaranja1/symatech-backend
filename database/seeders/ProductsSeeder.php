<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
    ['name'=>'Laptop', 'description'=>'Powerful laptop', 'price'=>1200.00, 'stock'=>10],
    ['name'=>'Mouse', 'description'=>'Wireless mouse', 'price'=>25.50, 'stock'=>50],
    ['name'=>'Keyboard', 'description'=>'Mechanical keyboard', 'price'=>80.00, 'stock'=>30],
    ['name'=>'Monitor', 'description'=>'24 inch monitor', 'price'=>200.00, 'stock'=>20],
    ['name'=>'Headphones', 'description'=>'Noise-cancelling', 'price'=>150.00, 'stock'=>15]
];

foreach($products as $product) {
    Product::create($product);
}
        
    }
}
