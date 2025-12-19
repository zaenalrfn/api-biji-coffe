<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartTest extends TestCase
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

    public function test_user_can_add_item_to_cart()
    {
        $response = $this->actingAs($this->user)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'notes' => 'Less sugar'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'user_id', 'product_id', 'quantity', 'notes']);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'notes' => 'Less sugar'
        ]);
    }

    public function test_user_can_view_cart()
    {
        $this->actingAs($this->user)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }
}
