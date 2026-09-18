<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Admin',
                'email' => env('ADMIN_EMAIL'),
                'password' => env('ADMIN_PASSWORD'),
            ],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($admin['password']),
                    'role' => 'admin',
                    'status' => 'approved',
                    'is_super_admin' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
