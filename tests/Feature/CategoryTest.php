<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_list_categories()
    {
        $user = User::factory()->create();
        Category::create(['name' => 'Test Category']);

        $response = $this->actingAs($user)->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'created_at', 'updated_at']
            ]);
    }

    public function test_can_create_category()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/categories', [
            'name' => 'New Category',
        ]);

        $response->assertStatus(201)
            ->assertJson(['name' => 'New Category']);

        $this->assertDatabaseHas('categories', ['name' => 'New Category']);
    }

    public function test_can_update_category()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'Updated Name']);

        $this->assertDatabaseHas('categories', ['name' => 'Updated Name']);
    }

    public function test_can_delete_category()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'To Delete']);

        $response = $this->actingAs($user)->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
