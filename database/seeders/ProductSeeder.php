<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coffee = Category::where('name', 'Coffee')->first();
        $nonCoffee = Category::where('name', 'Non-Coffee')->first();
        $snack = Category::where('name', 'Snack')->first();

        if ($coffee) {
            Product::create([
                'category_id' => $coffee->id,
                'title' => 'Espresso',
                'subtitle' => 'Strong coffee',
                'price' => 20000,
                'image' => null,
            ]);
            Product::create([
                'category_id' => $coffee->id,
                'title' => 'Cappuccino',
                'subtitle' => 'Espresso with milk foam',
                'price' => 25000,
                'image' => null,
            ]);
        }

        if ($nonCoffee) {
            Product::create([
                'category_id' => $nonCoffee->id,
                'title' => 'Matcha Latte',
                'subtitle' => 'Green tea with milk',
                'price' => 28000,
                'image' => null,
            ]);
        }

        if ($snack) {
            Product::create([
                'category_id' => $snack->id,
                'title' => 'Croissant',
                'subtitle' => 'Buttery pastry',
                'price' => 18000,
                'image' => null,
            ]);
        }
    }
}
