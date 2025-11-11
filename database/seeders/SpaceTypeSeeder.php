<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SpaceType;
use App\Models\Space;

class SpaceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $spaceTypes = SpaceType::getDefaultSpaceTypes();

        foreach ($spaceTypes as $spaceTypeData) {
            $spaceType = SpaceType::create([
                'name' => $spaceTypeData['name'],
                'total_slots' => $spaceTypeData['total_slots'],
                'available_slots' => $spaceTypeData['total_slots'], // Initially all available
                'default_price' => $spaceTypeData['default_price'],
                'description' => $this->getSpaceTypeDescription($spaceTypeData['name']),
            ]);

            // Create individual spaces for each type
            for ($i = 1; $i <= $spaceType->total_slots; $i++) {
                Space::create([
                    'space_type_id' => $spaceType->id,
                    'name' => $this->generateSpaceName($spaceType->name, $i),
                    'status' => 'available',
                ]);
            }
        }
    }

    private function getSpaceTypeDescription($name)
    {
        return match($name) {
            'PRIVATE SPACE' => 'Individual private workspace with desk and chair',
            'DRAFTING TABLE' => 'Large table suitable for architectural or design work',
            'CONFERENCE ROOM' => 'Meeting room for team discussions and presentations. Recommended for around 10 people.',
            'SHARED SPACE' => 'Open workspace area for collaborative work',
            'EXCLUSIVE SPACE' => 'Premium workspace with additional amenities',
            default => 'Workspace area'
        };
    }

    private function generateSpaceName($typeName, $number)
    {
        return match($typeName) {
            'PRIVATE SPACE' => "Private Space {$number}",
            'DRAFTING TABLE' => "Drafting Table " . chr(64 + $number), // A, B, C
            'CONFERENCE ROOM' => "Conference Room",
            'SHARED SPACE' => "Shared Space {$number}",
            'EXCLUSIVE SPACE' => "Exclusive Space {$number}",
            default => "{$typeName} {$number}"
        };
    }
}
