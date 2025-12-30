<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Driver User
        $driverUser = User::firstOrCreate(
            ['email' => 'driver@bijicoffee.com'],
            [
                'name' => 'Budi Driver',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $driverUser->assignRole('driver');

        // 2. Create Driver Record
        $driver = Driver::firstOrCreate(
            ['user_id' => $driverUser->id],
            [
                'name' => $driverUser->name,
                'phone' => '081234567890',
                'current_lat' => -6.2088,
                'current_lng' => 106.8456,
                'is_active' => true,
            ]
        );

        // 3. Create a Customer User for Orders
        $customer = User::firstOrCreate(
            ['email' => 'customer@bijicoffee.com'],
            [
                'name' => 'Siti Customer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $customer->assignRole('user');

        // Ensure we have products
        if (Product::count() == 0) {
            // Fallback if ProductSeeder hasn't run or is empty
            // Ideally ProductSeeder checks this too, but let's assume ProductSeeder runs before this.
        }
        $products = Product::inRandomOrder()->take(3)->get();

        if ($products->isEmpty()) {
            return; // Cannot create orders without products
        }

        // 4. Create Orders linked to Driver
        // Order 1: Assigned to Driver, Status: confirmed
        $order1 = Order::create([
            'user_id' => $customer->id,
            'driver_id' => $driver->id,
            'payment_method' => 'Midtrans',
            'shipping_address' => [
                'recipient_name' => $customer->name,
                'address_line' => 'Jl. Kebon Jeruk No. 1',
                'city' => 'Jakarta Barat',
                'state' => 'DKI Jakarta',
                'country' => 'Indonesia',
                'zip_code' => '11530'
            ],
            'total_price' => 50000,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'transaction_id' => 'ORDER-SEED-01',
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $products->first()->id,
            'quantity' => 2,
            'price' => $products->first()->price,
        ]);

        // Order 2: Assigned to Driver, Status: on_delivery
        $order2 = Order::create([
            'user_id' => $customer->id,
            'driver_id' => $driver->id,
            'payment_method' => 'Midtrans',
            'shipping_address' => [
                'recipient_name' => $customer->name,
                'address_line' => 'Jl. Palmerah No. 2',
                'city' => 'Jakarta Barat',
                'state' => 'DKI Jakarta',
                'country' => 'Indonesia',
                'zip_code' => '11480'
            ],
            'total_price' => 75000,
            'status' => 'on_delivery',
            'payment_status' => 'paid',
            'transaction_id' => 'ORDER-SEED-02',
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $products->last()->id,
            'quantity' => 1,
            'price' => $products->last()->price,
        ]);

        // Order 3: Pending Order (No Driver)
        $order3 = Order::create([
            'user_id' => $customer->id,
            'driver_id' => null,
            'payment_method' => 'Midtrans',
            'shipping_address' => [
                'recipient_name' => $customer->name,
                'address_line' => 'Jl. Sudirman No. 3',
                'city' => 'Jakarta Pusat',
                'state' => 'DKI Jakarta',
                'country' => 'Indonesia',
                'zip_code' => '10220'
            ],
            'total_price' => 100000,
            'status' => 'pending',
            'payment_status' => 'paid',
            'transaction_id' => 'ORDER-SEED-03',
        ]);

        OrderItem::create([
            'order_id' => $order3->id,
            'product_id' => $products->first()->id,
            'quantity' => 1,
            'price' => $products->first()->price,
        ]);
    }
}
