<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $category = Category::create([
            'name' => 'Test Category',
            'description' => 'Test Description',
            'image_url' => 'http://example.com/image.jpg'
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Tubruk',
            'description' => 'Kopi hitam asli',
            'price' => 15000,
            'image_url' => 'http://example.com/kopi.jpg',
            'stock' => 100,
            'rating' => 5.0
        ]);
    }

    public function test_user_can_checkout_order()
    {
        // Add item to cart first
        CartItem::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'notes' => 'Less sugar'
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'shipping_address' => [
                'recipient_name' => 'John Doe',
                'address_line' => 'Jl. Test No. 123',
                'city' => 'Jakarta',
                'state' => 'DKI Jakarta',
                'country' => 'Indonesia',
                'zip_code' => '12345'
            ],
            'payment_method' => 'Credit Card'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'total_price', 'status', 'items']);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'total_price' => 30000, // 15000 * 2,
            'shipping_address' => json_encode([
                'recipient_name' => 'John Doe',
                'address_line' => 'Jl. Test No. 123',
                'city' => 'Jakarta',
                'state' => 'DKI Jakarta',
                'country' => 'Indonesia',
                'zip_code' => '12345'
            ])
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $this->user->id
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);
    }
}
