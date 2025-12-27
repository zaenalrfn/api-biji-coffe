<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AssignRolesToExistingUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            if (!$user->hasAnyRole(['admin', 'user'])) {
                $user->assignRole('user');
                $this->command->info("Assigned 'user' role to: " . $user->email);
            }
        }
    }
}
