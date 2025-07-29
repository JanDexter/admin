<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if doesn't exist
        $adminUser = User::where('email', 'admin@admin.com')->first();
        if (!$adminUser) {
            User::create([
                'name' => 'System Administrator',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Create staff user if doesn't exist
        $staffUser = User::where('email', 'staff@example.com')->first();
        if (!$staffUser) {
            User::create([
                'name' => 'Staff Member',
                'email' => 'staff@example.com',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Create customer user if doesn't exist
        $customerUser = User::where('email', 'customer@example.com')->first();
        if (!$customerUser) {
            User::create([
                'name' => 'Test Customer',
                'email' => 'customer@example.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Update existing users without roles to be customers
        User::whereNull('role')->update([
            'role' => 'customer',
            'is_active' => true,
        ]);
    }
}
