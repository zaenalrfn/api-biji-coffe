<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // Create 2-5 random notifications for each user
            $count = rand(2, 5);

            for ($i = 0; $i < $count; $i++) {
                $type = ['system', 'promo', 'order', 'account'][rand(0, 3)];
                $title = '';
                $body = '';

                switch ($type) {
                    case 'system':
                        $title = 'System Maintenance';
                        $body = 'Scheduled maintenance on Sunday at 2 AM.';
                        break;
                    case 'promo':
                        $title = 'Weekend Sale!';
                        $body = 'Get 50% off on all coffee beans this weekend.';
                        break;
                    case 'order':
                        $title = 'Order Shipped';
                        $body = 'Your order #' . rand(1000, 9999) . ' has been shipped.';
                        break;
                    case 'account':
                        $title = 'Security Alert';
                        $body = 'New login detected from a new device.';
                        break;
                }

                $user->notifications()->create([
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'is_read' => (bool) rand(0, 1),
                    'created_at' => now()->subDays(rand(0, 10))->subHours(rand(0, 23)),
                ]);
            }
        }
    }
}
