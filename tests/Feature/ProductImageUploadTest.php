<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;

class ProductImageUploadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_upload_image_during_product_creation()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        $category = Category::factory()->create();

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/products', [
                    'category_id' => $category->id,
                    'title' => 'Test Product',
                    'price' => 10000,
                    'image' => $file,
                ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'title' => 'Test Product',
        ]);

        $product = Product::where('title', 'Test Product')->first();
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);

        // Verify image_url is present in response
        $response->assertJson([
            'image_url' => url('storage/' . $product->image),
        ]);
    }

    public function test_can_create_product_without_image()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/products', [
                    'category_id' => $category->id,
                    'title' => 'Product No Image',
                    'price' => 20000,
                ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'title' => 'Product No Image',
            'image' => null,
        ]);
    }

    public function test_can_update_product_image()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        $category = Category::factory()->create();

        $oldImage = UploadedFile::fake()->image('old.jpg');
        $path = $oldImage->store('products', 'public');

        $product = Product::factory()->create([
            'image' => $path,
            'category_id' => $category->id
        ]);

        $newImage = UploadedFile::fake()->image('new.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/products/{$product->id}", [
                    'image' => $newImage,
                ]);

        $response->assertStatus(200);

        $product->refresh();
        Storage::disk('public')->assertExists($product->image);
        Storage::disk('public')->assertMissing($path); // Old image should be gone
    }
}
