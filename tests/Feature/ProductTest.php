<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_list_products()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test Category']);
        Product::create([
            'category_id' => $category->id,
            'title' => 'Test Product',
            'price' => 10.50,
        ]);

        $response = $this->actingAs($user)->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'title', 'price', 'category']
            ]);
    }

    public function test_can_create_product()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test Category']);

        $productData = [
            'category_id' => $category->id,
            'title' => 'New Product',
            'subtitle' => 'Subtitle',
            'price' => 15.00,
            'image' => 'path/to/image.jpg',
        ];

        $response = $this->actingAs($user)->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'New Product']);

        $this->assertDatabaseHas('products', ['title' => 'New Product']);
    }

    public function test_can_update_product()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test Category']);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'Old Title',
            'price' => 10.00,
        ]);

        $response = $this->actingAs($user)->putJson("/api/products/{$product->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);

        $this->assertDatabaseHas('products', ['title' => 'Updated Title']);
    }

    public function test_can_delete_product()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test Category']);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'To Delete',
            'price' => 10.00,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
