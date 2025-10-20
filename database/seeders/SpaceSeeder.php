<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\SpaceType;
use Illuminate\Database\Seeder;

class SpaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Start from a clean slate to avoid duplicates when seeding multiple times
        Space::query()->delete();
        SpaceType::query()->delete();

        $defaultSpaceTypes = SpaceType::getDefaultSpaceTypes();

        foreach ($defaultSpaceTypes as $type) {
            $spaceType = SpaceType::create([
                'name' => ucwords(strtolower($type['name'])),
                'default_price' => $type['default_price'],
                'hourly_rate' => $type['hourly_rate'],
                'default_discount_hours' => $type['default_discount_hours'],
                'default_discount_percentage' => $type['default_discount_percentage'],
                'total_slots' => $type['total_slots'],
                'available_slots' => $type['total_slots'],
                'description' => $type['description'] ?? null,
            ]);

            for ($i = 1; $i <= $type['total_slots']; $i++) {
                Space::create([
                    'space_type_id' => $spaceType->id,
                    'name' => sprintf('%s #%02d', ucwords(strtolower($type['name'])), $i),
                    'status' => 'available',
                    'hourly_rate' => $spaceType->hourly_rate,
                    'discount_hours' => $spaceType->default_discount_hours,
                    'discount_percentage' => $spaceType->default_discount_percentage,
                ]);
            }
        }
    }
}
