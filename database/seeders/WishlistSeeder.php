<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Wishlist;

class WishlistSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        $products = Product::all();

        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // Assign 1-3 random products to each user's wishlist
            $randomProducts = $products->random(min(3, $products->count()));

            foreach ($randomProducts as $product) {
                // Check if already exists to avoid duplicate error if re-seeding without clearing
                if (!Wishlist::where('user_id', $user->id)->where('product_id', $product->id)->exists()) {
                    Wishlist::create([
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                    ]);
                }
            }
        }
    }
}
