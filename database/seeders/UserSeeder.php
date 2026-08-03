<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@example.test',
                'password' => Hash::make(env('HSP_ADMIN_PASSWORD', 'ChangeMeAdmin123!')),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['username' => 'user'],
            [
                'name' => 'Pengguna HSP',
                'email' => 'user@example.test',
                'password' => Hash::make(env('HSP_USER_PASSWORD', 'ChangeMeUser123!')),
                'role' => 'user',
                'is_active' => true,
            ]
        );
    }
}
