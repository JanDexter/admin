<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Reservation;
use App\Models\TransactionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user (should already exist)
        $admin = User::first(); // Get the first user (admin)
        
        if (!$admin) {
            $this->command->error('No users found. Please create an admin user first.');
            return;
        }

        // Create sample customers
        $customers = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '09171234567',
                'contact_person' => 'John Doe',
                'status' => 'active',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '09181234567',
                'contact_person' => 'Jane Smith',
                'status' => 'active',
            ],
            [
                'company_name' => 'Tech Startup Inc.',
                'name' => 'Mike Johnson',
                'email' => 'mike@techstartup.com',
                'phone' => '09191234567',
                'contact_person' => 'Mike Johnson',
                'status' => 'active',
            ],
            [
                'company_name' => 'Creative Agency',
                'name' => 'Sarah Williams',
                'email' => 'sarah@creative.com',
                'phone' => '09201234567',
                'contact_person' => 'Sarah Williams',
                'status' => 'active',
            ],
            [
                'name' => 'David Brown',
                'email' => 'david@example.com',
                'phone' => '09211234567',
                'contact_person' => 'David Brown',
                'status' => 'inactive',
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily@example.com',
                'phone' => '09221234567',
                'contact_person' => 'Emily Davis',
                'status' => 'active',
            ],
            [
                'company_name' => 'Marketing Pro',
                'name' => 'Robert Wilson',
                'email' => 'robert@marketingpro.com',
                'phone' => '09231234567',
                'contact_person' => 'Robert Wilson',
                'status' => 'active',
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa@example.com',
                'phone' => '09241234567',
                'contact_person' => 'Lisa Anderson',
                'status' => 'active',
            ],
        ];

        $createdCustomers = [];
        foreach ($customers as $customerData) {
            $createdCustomers[] = Customer::firstOrCreate(
                ['email' => $customerData['email']],
                $customerData
            );
        }

        // Get space types and spaces
        $sharedSpace = SpaceType::where('name', 'Shared Space')->first();
        $privateSpace = SpaceType::where('name', 'Private Space')->first();
        $conferenceRoom = SpaceType::where('name', 'Conference Room')->first();

        if (!$sharedSpace || !$privateSpace || !$conferenceRoom) {
            $this->command->error('Space types not found. Please run SpaceSeeder first.');
            return;
        }

        $spaces = Space::all();
        if ($spaces->isEmpty()) {
            $this->command->error('Spaces not found. Please run SpaceSeeder first.');
            return;
        }

        // Create sample reservations with different statuses and times
        $now = Carbon::now();
        $reservations = [];

        // 1. Active reservations (currently in use)
        $reservations[] = [
            'customer_id' => $createdCustomers[0]->id,
            'space_id' => $spaces->where('space_type_id', $sharedSpace->id)->first()->id,
            'space_type_id' => $sharedSpace->id,
            'user_id' => $admin->id,
            'start_time' => $now->copy()->subHours(2),
            'end_time' => null,
            'hours' => 2, // Set estimated hours for open-time
            'pax' => 1,
            'status' => 'active',
            'payment_method' => 'cash',
            'is_open_time' => true,
            'applied_hourly_rate' => $sharedSpace->hourly_rate,
            'amount_paid' => 0,
            'notes' => 'Currently working on project',
        ];

        $reservations[] = [
            'customer_id' => $createdCustomers[1]->id,
            'space_id' => $spaces->where('space_type_id', $privateSpace->id)->first()->id,
            'space_type_id' => $privateSpace->id,
            'user_id' => $admin->id,
            'start_time' => $now->copy()->subHours(3),
            'end_time' => $now->copy()->addHours(2),
            'hours' => 5,
            'pax' => 1,
            'status' => 'active',
            'payment_method' => 'gcash',
            'is_open_time' => false,
            'applied_hourly_rate' => $privateSpace->hourly_rate,
            'cost' => $privateSpace->hourly_rate * 5,
            'amount_paid' => $privateSpace->hourly_rate * 5,
            'notes' => 'Private meeting space',
        ];

        // 2. Confirmed future reservations
        $reservations[] = [
            'customer_id' => $createdCustomers[2]->id,
            'space_id' => $spaces->where('space_type_id', $conferenceRoom->id)->first()->id,
            'space_type_id' => $conferenceRoom->id,
            'user_id' => $admin->id,
            'start_time' => $now->copy()->addHours(4),
            'end_time' => $now->copy()->addHours(7),
            'hours' => 3,
            'pax' => 8,
            'status' => 'confirmed',
            'payment_method' => 'bank',
            'is_open_time' => false,
            'applied_hourly_rate' => $conferenceRoom->hourly_rate,
            'cost' => $conferenceRoom->hourly_rate * 3,
            'amount_paid' => $conferenceRoom->hourly_rate * 3,
            'notes' => 'Team meeting scheduled',
        ];

        $reservations[] = [
            'customer_id' => $createdCustomers[3]->id,
            'space_id' => $spaces->where('space_type_id', $sharedSpace->id)->skip(1)->first()->id,
            'space_type_id' => $sharedSpace->id,
            'user_id' => $admin->id,
            'start_time' => $now->copy()->addDay(),
            'end_time' => $now->copy()->addDay()->addHours(4),
            'hours' => 4,
            'pax' => 2,
            'status' => 'confirmed',
            'payment_method' => 'gcash',
            'is_open_time' => false,
            'applied_hourly_rate' => $sharedSpace->hourly_rate,
            'cost' => $sharedSpace->hourly_rate * 4,
            'amount_paid' => 0,
            'notes' => 'Collaborative work session',
        ];

        // 3. Completed reservations (recent past)
        for ($i = 0; $i < 10; $i++) {
            $customer = $createdCustomers[array_rand($createdCustomers)];
            $spaceType = collect([$sharedSpace, $privateSpace, $conferenceRoom])->random();
            $space = $spaces->where('space_type_id', $spaceType->id)->random();
            $hours = rand(2, 8);
            $daysAgo = rand(1, 30);
            
            $startTime = $now->copy()->subDays($daysAgo)->setHour(rand(9, 16));
            $endTime = $startTime->copy()->addHours($hours);
            $cost = $spaceType->hourly_rate * $hours;

            $reservations[] = [
                'customer_id' => $customer->id,
                'space_id' => $space->id,
                'space_type_id' => $spaceType->id,
                'user_id' => $admin->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hours' => $hours,
                'pax' => rand(1, 10),
                'status' => 'completed',
                'payment_method' => collect(['cash', 'gcash', 'maya', 'bank'])->random(),
                'is_open_time' => false,
                'applied_hourly_rate' => $spaceType->hourly_rate,
                'cost' => $cost,
                'amount_paid' => $cost,
                'notes' => 'Completed session',
            ];
        }

        // 4. Pending reservations
        $reservations[] = [
            'customer_id' => $createdCustomers[5]->id,
            'space_id' => $spaces->where('space_type_id', $privateSpace->id)->skip(1)->first()->id,
            'space_type_id' => $privateSpace->id,
            'user_id' => $admin->id,
            'start_time' => $now->copy()->addDays(2),
            'end_time' => $now->copy()->addDays(2)->addHours(3),
            'hours' => 3,
            'pax' => 1,
            'status' => 'pending',
            'payment_method' => null,
            'is_open_time' => false,
            'applied_hourly_rate' => $privateSpace->hourly_rate,
            'cost' => $privateSpace->hourly_rate * 3,
            'amount_paid' => 0,
            'hold_until' => $now->copy()->addHours(24),
            'notes' => 'Pending payment confirmation',
        ];

        // 5. Cancelled reservation
        $reservations[] = [
            'customer_id' => $createdCustomers[6]->id,
            'space_id' => $spaces->where('space_type_id', $conferenceRoom->id)->first()->id,
            'space_type_id' => $conferenceRoom->id,
            'user_id' => $admin->id,
            'start_time' => $now->copy()->subDays(5),
            'end_time' => $now->copy()->subDays(5)->addHours(4),
            'hours' => 4,
            'pax' => 6,
            'status' => 'cancelled',
            'payment_method' => 'gcash',
            'is_open_time' => false,
            'applied_hourly_rate' => $conferenceRoom->hourly_rate,
            'cost' => $conferenceRoom->hourly_rate * 4,
            'amount_paid' => $conferenceRoom->hourly_rate * 4,
            'notes' => 'Meeting cancelled',
        ];

        // Create all reservations
        $createdReservations = [];
        foreach ($reservations as $reservationData) {
            $createdReservations[] = Reservation::create($reservationData);
        }

        // Create transaction logs for completed and active reservations
        foreach ($createdReservations as $reservation) {
            if (in_array($reservation->status, ['completed', 'active']) && $reservation->amount_paid > 0) {
                TransactionLog::create([
                    'type' => 'payment',
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'processed_by' => $admin->id,
                    'amount' => $reservation->amount_paid,
                    'payment_method' => $reservation->payment_method,
                    'status' => 'completed',
                    'description' => 'Payment for ' . $reservation->spaceType->name,
                    'reference_number' => TransactionLog::generateReferenceNumber('payment'),
                    'created_at' => $reservation->start_time,
                ]);
            }

            // Add a refund transaction for the cancelled reservation
            if ($reservation->status === 'cancelled' && $reservation->amount_paid > 0) {
                $refundAmount = $reservation->amount_paid * 0.8; // 80% refund
                
                TransactionLog::create([
                    'type' => 'refund',
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'processed_by' => $admin->id,
                    'amount' => -$refundAmount,
                    'payment_method' => $reservation->payment_method,
                    'status' => 'completed',
                    'description' => 'Refund for cancelled reservation',
                    'reference_number' => TransactionLog::generateReferenceNumber('refund'),
                    'notes' => '80% refund processed',
                    'created_at' => $reservation->end_time,
                ]);

                TransactionLog::create([
                    'type' => 'cancellation',
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'processed_by' => $admin->id,
                    'amount' => 0,
                    'status' => 'completed',
                    'description' => 'Reservation cancellation',
                    'reference_number' => TransactionLog::generateReferenceNumber('cancellation'),
                    'notes' => 'Customer requested cancellation',
                    'created_at' => $reservation->end_time,
                ]);
            }
        }

        $this->command->info('Sample data created successfully!');
        $this->command->info('Created ' . count($createdCustomers) . ' customers');
        $this->command->info('Created ' . count($createdReservations) . ' reservations');
        $this->command->info('Created transaction logs for payments, refunds, and cancellations');
    }
}
