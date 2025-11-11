<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class DemoMultiRoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example 1: Super Admin who is also a Customer (can use the workspace!)
        $adminCustomer = User::firstOrCreate(
            ['email' => 'admin@coz.com'],
            [
                'name' => 'John Admin',
                'phone' => '09171234567',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Create admin profile
        Admin::firstOrCreate(
            ['user_id' => $adminCustomer->id],
            [
                'permission_level' => 'super_admin',
                'permissions' => ['*'], // All permissions
            ]
        );

        // Create customer profile (so admin can book spaces!)
        Customer::firstOrCreate(
            ['user_id' => $adminCustomer->id],
            [
                'name' => $adminCustomer->name,
                'email' => $adminCustomer->email,
                'phone' => $adminCustomer->phone,
                'status' => 'active',
            ]
        );

        $this->command->info("✅ Created: {$adminCustomer->name} (Admin + Customer)");

        // Example 2: Staff member who is also a Customer
        $staffCustomer = User::firstOrCreate(
            ['email' => 'staff@coz.com'],
            [
                'name' => 'Maria Staff',
                'phone' => '09187654321',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Create staff profile
        Staff::firstOrCreate(
            ['user_id' => $staffCustomer->id],
            [
                'employee_id' => 'EMP001',
                'department' => 'Front Desk',
                'hourly_rate' => 150.00,
                'hired_date' => now()->subMonths(6),
            ]
        );

        // Create customer profile
        Customer::firstOrCreate(
            ['user_id' => $staffCustomer->id],
            [
                'name' => $staffCustomer->name,
                'email' => $staffCustomer->email,
                'phone' => $staffCustomer->phone,
                'status' => 'active',
            ]
        );

        $this->command->info("✅ Created: {$staffCustomer->name} (Staff + Customer)");

        // Example 3: Regular Customer Only
        $regularCustomer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Juan Customer',
                'phone' => '09191234567',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Customer::firstOrCreate(
            ['user_id' => $regularCustomer->id],
            [
                'name' => $regularCustomer->name,
                'email' => $regularCustomer->email,
                'phone' => $regularCustomer->phone,
                'company_name' => 'Tech Startup Inc.',
                'status' => 'active',
            ]
        );

        $this->command->info("✅ Created: {$regularCustomer->name} (Customer Only)");

        // Example 4: Admin Only (no customer profile - unusual but valid)
        $adminOnly = User::firstOrCreate(
            ['email' => 'sysadmin@coz.com'],
            [
                'name' => 'System Administrator',
                'phone' => '09201234567',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Admin::firstOrCreate(
            ['user_id' => $adminOnly->id],
            [
                'permission_level' => 'admin',
                'permissions' => ['manage_spaces', 'view_reports'],
            ]
        );

        $this->command->info("✅ Created: {$adminOnly->name} (Admin Only - cannot book spaces)");

        // Example 5: Staff Only
        $staffOnly = User::firstOrCreate(
            ['email' => 'receptionist@coz.com'],
            [
                'name' => 'Anna Receptionist',
                'phone' => '09211234567',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Staff::firstOrCreate(
            ['user_id' => $staffOnly->id],
            [
                'employee_id' => 'EMP002',
                'department' => 'Reception',
                'hourly_rate' => 120.00,
                'hired_date' => now()->subMonths(3),
            ]
        );

        $this->command->info("✅ Created: {$staffOnly->name} (Staff Only)");

        $this->command->newLine();
        $this->command->info("🎉 Demo users created successfully!");
        $this->command->newLine();
        $this->command->table(
            ['Email', 'Password', 'Roles'],
            [
                ['admin@coz.com', 'password', 'Admin + Customer (can book spaces!)'],
                ['staff@coz.com', 'password', 'Staff + Customer (can book spaces!)'],
                ['customer@example.com', 'password', 'Customer Only'],
                ['sysadmin@coz.com', 'password', 'Admin Only (cannot book)'],
                ['receptionist@coz.com', 'password', 'Staff Only'],
            ]
        );
    }
}
