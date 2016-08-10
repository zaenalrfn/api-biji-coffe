<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@bijicoffee.com'],
            [
                'name' => 'Admin Biji Coffee',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Create Regular User
        $user = User::firstOrCreate(
            ['email' => 'user@bijicoffee.com'],
            [
                'name' => 'User Biji Coffee',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole('user');

        // Factory generated users
        User::factory(5)->create()->each(function ($u) {
            $u->assignRole('user');
        });
    }
}
