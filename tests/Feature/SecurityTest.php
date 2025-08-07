<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_user_management_and_customer_management()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('user-management.index'))
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function customer_cannot_access_user_management()
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('user-management.index'))
            ->assertForbidden();
    }

    /** @test */
    public function staff_can_access_staff_routes_but_not_admin_only_routes()
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('user-management.index'))
            ->assertForbidden();
    }

    /** @test */
    public function guest_cannot_access_protected_routes()
    {
        $this->get(route('user-management.index'))->assertRedirect(route('login'));
        $this->get(route('customers.index'))->assertRedirect(route('login'));
    }
}
