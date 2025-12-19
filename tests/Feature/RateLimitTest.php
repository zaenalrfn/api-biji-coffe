<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;

class RateLimitTest extends TestCase
{
    use DatabaseTransactions;

    public function test_write_routes_are_rate_limited()
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Make 10 requests - should be successful (or at least not 429)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson('/api/user', [
                        'name' => 'New Name ' . $i,
                        'email' => $user->email,
                    ]);

            // We expect success because we are updating with valid data
            // But main thing is it shouldn't be 429
            $response->assertStatus(200);
        }

        // The 11th request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/user', [
                    'name' => 'New Name 11',
                    'email' => $user->email,
                ]);

        $response->assertStatus(429);
    }

    public function test_read_routes_are_not_rate_limited_by_write_limiter()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Make 15 requests - should be all successful
        for ($i = 0; $i < 15; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/user');

            $response->assertStatus(200);
        }
    }
}
