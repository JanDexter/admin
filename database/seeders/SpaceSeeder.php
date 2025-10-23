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
        $defaultSpaceTypes = SpaceType::getDefaultSpaceTypes();

        foreach ($defaultSpaceTypes as $type) {
            $name = ucwords(strtolower($type['name']));

            $spaceType = SpaceType::firstOrCreate(
                ['name' => $name],
                [
                    'default_price' => $type['default_price'],
                    'hourly_rate' => $type['hourly_rate'],
                    'default_discount_hours' => $type['default_discount_hours'],
                    'default_discount_percentage' => $type['default_discount_percentage'],
                    'total_slots' => $type['total_slots'],
                    'available_slots' => $type['total_slots'],
                    'description' => $type['description'] ?? null,
                ]
            );

            $updates = [];

            if (is_null($spaceType->hourly_rate)) {
                $updates['hourly_rate'] = $type['hourly_rate'];
            }

            if (is_null($spaceType->default_discount_hours)) {
                $updates['default_discount_hours'] = $type['default_discount_hours'];
            }

            if (is_null($spaceType->default_discount_percentage)) {
                $updates['default_discount_percentage'] = $type['default_discount_percentage'];
            }

            if (is_null($spaceType->default_price) || $spaceType->default_price == 0) {
                $updates['default_price'] = $type['default_price'];
            }

            $desiredSlots = max((int) $spaceType->total_slots, (int) $type['total_slots']);

            if ($spaceType->total_slots !== $desiredSlots) {
                $updates['total_slots'] = $desiredSlots;
            }

            if (!empty($updates)) {
                $spaceType->fill($updates);
                $spaceType->save();
            }

            for ($i = 1; $i <= $desiredSlots; $i++) {
                $spaceName = sprintf('%s #%02d', $name, $i);

                $space = Space::firstOrCreate(
                    [
                        'space_type_id' => $spaceType->id,
                        'name' => $spaceName,
                    ],
                    [
                        'status' => 'available',
                        'hourly_rate' => $spaceType->hourly_rate ?? $type['hourly_rate'],
                        'discount_hours' => $spaceType->default_discount_hours ?? $type['default_discount_hours'],
                        'discount_percentage' => $spaceType->default_discount_percentage ?? $type['default_discount_percentage'],
                    ]
                );

                if ($space->wasRecentlyCreated) {
                    $spaceType->increment('available_slots');
                }
            }

            $occupiedCount = $spaceType->spaces()->where('status', 'occupied')->count();
            $calculatedAvailable = max(0, $spaceType->spaces()->count() - $occupiedCount);

            if ($spaceType->available_slots !== $calculatedAvailable) {
                $spaceType->update(['available_slots' => $calculatedAvailable]);
            }
        }
    }
}
