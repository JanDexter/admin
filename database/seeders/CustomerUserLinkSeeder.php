<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;

class CustomerUserLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all customer users who don't have a customer record yet
        $customerUsers = User::where('role', 'customer')
            ->whereDoesntHave('customer')
            ->get();

        foreach ($customerUsers as $user) {
            Customer::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => 'active',
            ]);
        }

        $this->command->info('Created customer records for ' . $customerUsers->count() . ' customer users.');
    }
}
