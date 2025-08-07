<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function admin_can_access_all_management_routes()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/user-management')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get(route('dashboard'))
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
            ->get('/user-management')
            ->assertStatus(403);
    }

    /** @test */
    public function staff_cannot_access_user_management()
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get('/user-management')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_users_are_redirected_to_login()
    {
        $this->get('/user-management')
            ->assertRedirect(route('login'));

        $this->get(route('customers.index'))
            ->assertRedirect(route('login'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_create_customers()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('customers.create'))
            ->post(route('customers.store'), [
                '_token' => csrf_token(),
                'company_name' => 'Test Company',
                'contact_person' => 'John Doe',
                'email' => 'test@example.com',
                'phone' => '123-456-7890',
                'address' => '123 Test St',
                'status' => 'active',
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'test@example.com',
            'company_name' => 'Test Company',
        ]);
    }

    /** @test */
    public function customers_can_access_customer_routes()
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('customers.index'))
            ->assertStatus(200);
    }
}
