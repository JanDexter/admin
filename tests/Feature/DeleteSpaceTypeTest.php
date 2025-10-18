<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use App\Models\SpaceType;
use App\Models\Space;
use App\Models\User;

class DeleteSpaceTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');
    }

    protected function actingAsAdmin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->be($user);
        return $user;
    }

    public function test_can_delete_space_type_when_no_occupied_spaces(): void
    {
        $this->actingAsAdmin();

        $type = SpaceType::factory()->create([
            'total_slots' => 2,
            'available_slots' => 2,
        ]);
        // Create 2 available spaces
        Space::factory()->create(['space_type_id' => $type->id, 'status' => 'available']);
        Space::factory()->create(['space_type_id' => $type->id, 'status' => 'available']);

        $response = $this->delete(route('space-management.destroy-space-type', $type->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('space_types', ['id' => $type->id]);
        $this->assertDatabaseMissing('spaces', ['space_type_id' => $type->id]);
    }

    public function test_cannot_delete_space_type_with_occupied_spaces(): void
    {
        $this->actingAsAdmin();

        $type = SpaceType::factory()->create([
            'total_slots' => 1,
            'available_slots' => 0,
        ]);
        // Create one occupied space
        Space::factory()->create(['space_type_id' => $type->id, 'status' => 'occupied']);

        $response = $this->delete(route('space-management.destroy-space-type', $type->id));
        $response->assertRedirect();

        $this->assertDatabaseHas('space_types', ['id' => $type->id]);
    }
}
