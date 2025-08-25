<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateEmailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function duplicate_emails_are_prevented_in_user_creation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create first user with an email
        User::factory()->create(['email' => 'test@example.com']);

        // Try to create second user with same email via user management
        $response = $this->actingAs($admin)
            ->post(route('user-management.store'), [
                'name' => 'Test User 2',
                'email' => 'test@example.com', // Same email
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'customer',
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('users', 2); // Only 2 users (admin + first user)
    }

    /** @test */
    public function duplicate_emails_are_prevented_in_customer_creation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create first customer with an email
        $response = $this->actingAs($admin)
            ->post(route('customers.store'), [
                'company_name' => 'Test Company',
                'contact_person' => 'John Doe',
                'email' => 'customer@example.com',
                'status' => 'active',
            ]);

        // Try to create second customer with same email
        $response = $this->actingAs($admin)
            ->post(route('customers.store'), [
                'company_name' => 'Test Company 2',
                'contact_person' => 'Jane Doe',
                'email' => 'customer@example.com', // Same email
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('customers', 1); // Only 1 customer created
    }
}
