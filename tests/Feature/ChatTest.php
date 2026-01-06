<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Product;
use App\Models\Category;
use App\Models\Store;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Event;
use App\Events\MessageSent;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $driverUser;
    protected $order;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            // Setup Roles
            Role::create(['name' => 'user']);
            Role::create(['name' => 'driver']);
            Role::create(['name' => 'admin']);

            // Setup Data
            $this->user = User::factory()->create()->assignRole('user');
            
            $this->driverUser = User::factory()->create()->assignRole('driver');
            $driver = Driver::create([
                'user_id' => $this->driverUser->id,
                'is_available' => true
            ]);

            $category = Category::create(['name' => 'Coffee']);
            $store = Store::create([
                 'name' => 'Biji Coffee', 
                 'address' => 'Jakarta',
                 'latitude' => 0, 'longitude' => 0,
                 'open_time' => '08:00', 'close_time' => '22:00'
            ]);

            $this->order = Order::create([
                'user_id' => $this->user->id,
                'driver_id' => $driver->id,
                'total_amount' => 20000,
                'status' => 'driver_assigned',
                'shipping_address' => json_encode(['address' => 'Test Address']),
                'payment_method' => 'cash'
            ]);
        } catch (\Throwable $e) {
            dump($e->getMessage());
            dump($e->getTraceAsString());
            throw $e;
        }
    }

    public function test_user_can_send_message()
    {
        Event::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/orders/{$this->order->id}/messages", [
                'message' => 'Halo Driver, dimana?'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('order_messages', [
            'order_id' => $this->order->id,
            'sender_id' => $this->user->id,
            'message' => 'Halo Driver, dimana?'
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_driver_can_reply_message()
    {
        Event::fake();

        $response = $this->actingAs($this->driverUser)
            ->postJson("/api/orders/{$this->order->id}/messages", [
                'message' => 'Halo Kak, saya sudah di jalan.'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('order_messages', [
            'sender_id' => $this->driverUser->id,
            'message' => 'Halo Kak, saya sudah di jalan.'
        ]);
    }

    public function test_unauthorized_user_cannot_read_chat()
    {
        $stranger = User::factory()->create()->assignRole('user');

        $response = $this->actingAs($stranger)
            ->getJson("/api/orders/{$this->order->id}/messages");

        $response->assertStatus(403);
    }

    public function test_chat_list_contains_correct_data()
    {
        // User send message
        $this->actingAs($this->user)
            ->postJson("/api/orders/{$this->order->id}/messages", [
                'message' => 'Tes Chat List'
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chat-list");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'order_id' => $this->order->id,
                'last_message' => 'Tes Chat List'
            ]);
    }
}
