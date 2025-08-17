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
        // Ensure admin user exists and is in a known good state
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                // email_verified_at may be ignored by mass-assignment, set below via forceFill
            ]
        );

        // Guarantee verified email timestamp
        if (! $admin->email_verified_at) {
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        // Demote any other admins to staff to enforce single-admin policy
        User::where('role', User::ROLE_ADMIN)
            ->where('id', '!=', $admin->id)
            ->update(['role' => User::ROLE_STAFF]);

        // Update existing users without roles to be customers (if any exist)
        User::whereNull('role')->update([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }
}
