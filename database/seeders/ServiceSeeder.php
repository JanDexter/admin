<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Conference Room',
                'description' => 'Professional conference room for meetings and presentations',
                'hourly_rate' => 350.00,
                'daily_rate' => 2800.00,
                'monthly_rate' => 70000.00,
                'capacity' => 12,
                'amenities' => json_encode([
                    'Projector',
                    'Whiteboard',
                    'Conference table',
                    'Chairs',
                    'Air conditioning',
                    'WiFi',
                    'Power outlets'
                ]),
                'availability_hours' => json_encode([
                    'monday' => ['start' => '08:00', 'end' => '18:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '18:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '18:00'],
                    'thursday' => ['start' => '08:00', 'end' => '18:00'],
                    'friday' => ['start' => '08:00', 'end' => '18:00'],
                    'saturday' => ['start' => '09:00', 'end' => '17:00'],
                    'sunday' => ['start' => '09:00', 'end' => '17:00']
                ]),
                'status' => 'active'
            ],
            [
                'name' => 'Shared Space',
                'description' => 'Open shared workspace with hot desks',
                'hourly_rate' => 40.00,
                'daily_rate' => 320.00,
                'monthly_rate' => 8000.00,
                'capacity' => 20,
                'amenities' => json_encode([
                    'Hot desks',
                    'WiFi',
                    'Power outlets',
                    'Shared kitchen',
                    'Printing facilities',
                    'Air conditioning'
                ]),
                'availability_hours' => json_encode([
                    'monday' => ['start' => '07:00', 'end' => '20:00'],
                    'tuesday' => ['start' => '07:00', 'end' => '20:00'],
                    'wednesday' => ['start' => '07:00', 'end' => '20:00'],
                    'thursday' => ['start' => '07:00', 'end' => '20:00'],
                    'friday' => ['start' => '07:00', 'end' => '20:00'],
                    'saturday' => ['start' => '08:00', 'end' => '18:00'],
                    'sunday' => ['start' => '08:00', 'end' => '18:00']
                ]),
                'status' => 'active'
            ],
            [
                'name' => 'Exclusive Space',
                'description' => 'Private workspace for exclusive use',
                'hourly_rate' => 60.00,
                'daily_rate' => 480.00,
                'monthly_rate' => 12000.00,
                'capacity' => 4,
                'amenities' => json_encode([
                    'Private office',
                    'Desk and chairs',
                    'WiFi',
                    'Power outlets',
                    'Air conditioning',
                    'Lock and key access'
                ]),
                'availability_hours' => json_encode([
                    'monday' => ['start' => '07:00', 'end' => '20:00'],
                    'tuesday' => ['start' => '07:00', 'end' => '20:00'],
                    'wednesday' => ['start' => '07:00', 'end' => '20:00'],
                    'thursday' => ['start' => '07:00', 'end' => '20:00'],
                    'friday' => ['start' => '07:00', 'end' => '20:00'],
                    'saturday' => ['start' => '08:00', 'end' => '18:00'],
                    'sunday' => ['start' => '08:00', 'end' => '18:00']
                ]),
                'status' => 'active'
            ],
            [
                'name' => 'Private Space',
                'description' => 'Small private workspace for individual use',
                'hourly_rate' => 50.00,
                'daily_rate' => 400.00,
                'monthly_rate' => 10000.00,
                'capacity' => 2,
                'amenities' => json_encode([
                    'Private desk',
                    'Chair',
                    'WiFi',
                    'Power outlets',
                    'Air conditioning',
                    'Storage space'
                ]),
                'availability_hours' => json_encode([
                    'monday' => ['start' => '07:00', 'end' => '20:00'],
                    'tuesday' => ['start' => '07:00', 'end' => '20:00'],
                    'wednesday' => ['start' => '07:00', 'end' => '20:00'],
                    'thursday' => ['start' => '07:00', 'end' => '20:00'],
                    'friday' => ['start' => '07:00', 'end' => '20:00'],
                    'saturday' => ['start' => '08:00', 'end' => '18:00'],
                    'sunday' => ['start' => '08:00', 'end' => '18:00']
                ]),
                'status' => 'active'
            ],
            [
                'name' => 'Drafting Table',
                'description' => 'Specialized drafting table for technical work',
                'hourly_rate' => 50.00,
                'daily_rate' => 400.00,
                'monthly_rate' => 10000.00,
                'capacity' => 1,
                'amenities' => json_encode([
                    'Adjustable drafting table',
                    'Drafting chair',
                    'Task lighting',
                    'Storage drawers',
                    'Power outlets',
                    'WiFi'
                ]),
                'availability_hours' => json_encode([
                    'monday' => ['start' => '07:00', 'end' => '20:00'],
                    'tuesday' => ['start' => '07:00', 'end' => '20:00'],
                    'wednesday' => ['start' => '07:00', 'end' => '20:00'],
                    'thursday' => ['start' => '07:00', 'end' => '20:00'],
                    'friday' => ['start' => '07:00', 'end' => '20:00'],
                    'saturday' => ['start' => '08:00', 'end' => '18:00'],
                    'sunday' => ['start' => '08:00', 'end' => '18:00']
                ]),
                'status' => 'active'
            ]
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
