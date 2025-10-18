<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use App\Models\SpaceType;
use App\Models\Reservation;
use Carbon\Carbon;

class PublicReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Some existing projects have duplicate reservations table migrations; run migrations fresh
        Artisan::call('migrate');
    }

    public function test_can_create_paid_reservation_with_gcash(): void
    {
        $spaceType = SpaceType::factory()->create();

        $response = $this->postJson(route('public.reservations.store'), [
            'space_type_id' => $spaceType->id,
            'payment_method' => 'gcash',
            'hours' => 3,
            'pax' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonPath('reservation.payment_method', 'gcash')
            ->assertJsonPath('reservation.status', 'paid')
            ->assertJsonPath('reservation.hours', 3)
            ->assertJsonPath('reservation.pax', 2);

        $this->assertDatabaseHas('public_reservations', [
            'space_type_id' => $spaceType->id,
            'payment_method' => 'gcash',
            'status' => 'paid',
            'hours' => 3,
            'pax' => 2,
        ]);
    }

    public function test_cash_reservation_is_hold_for_one_hour(): void
    {
        Carbon::setTestNow('2025-10-19 00:00:00');
        $spaceType = SpaceType::factory()->create();

        $response = $this->postJson(route('public.reservations.store'), [
            'space_type_id' => $spaceType->id,
            'payment_method' => 'cash',
            // hours should be ignored for cash and set to 1
            'hours' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonPath('reservation.payment_method', 'cash')
            ->assertJsonPath('reservation.status', 'hold')
            ->assertJsonPath('reservation.hours', 1);

        $res = Reservation::first();
        $this->assertNotNull($res->hold_until);
        $this->assertEquals(Carbon::now()->addHour()->toDateTimeString(), $res->hold_until->toDateTimeString());
    }

    public function test_validation_errors(): void
    {
        $response = $this->postJson(route('public.reservations.store'), [
            // missing space_type_id, payment_method invalid
            'payment_method' => 'credit',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['space_type_id', 'payment_method']);
    }
}
