<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $alex = User::updateOrCreate(
            ['email' => 'admin@connectly.test'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_seen_at' => now(),
            ]
        );
    }
}
