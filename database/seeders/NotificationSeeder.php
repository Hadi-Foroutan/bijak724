<?php

namespace Database\Seeders;

use App\Enums\NotificationType;
use App\Enums\RoleEnum;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sender = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                RoleEnum::ADMIN->value,
                RoleEnum::SUPERADMIN->value,
            ]))
            ->first();

        if ($sender === null) {
            return;
        }

        Notification::factory()
            ->for($sender, 'sender')
            ->create([
                'type' => NotificationType::Info,
                'should_remove_previous' => false,
                'message' => __('public.notification_welcome_message'),
            ]);
    }
}
