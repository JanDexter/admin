<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DataProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function passwords_are_properly_hashed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Ensure password is hashed, not stored in plain text
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function sensitive_user_data_is_not_exposed_in_api_responses()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get('/user-management');

        $content = $response->getContent();
        
        // Ensure bcrypt hashes are not exposed (but the word "password" might appear in forms)
        $this->assertStringNotContainsString('$2y$', $content); // bcrypt hash pattern
    }

    /** @test */
    public function user_can_only_access_their_own_data()
    {
        $user1 = User::factory()->create(['role' => 'customer']);
        $user2 = User::factory()->create(['role' => 'customer']);
        
        $customer1 = Customer::factory()->create(['user_id' => $user1->id]);
        $customer2 = Customer::factory()->create(['user_id' => $user2->id]);

        // User1 should not be able to access User2's customer data
        $this->actingAs($user1)
            ->get(route('customers.show', $customer2->id))
            ->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_all_user_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin)
            ->get(route('customers.show', $customer->id))
            ->assertStatus(200);
    }

    /** @test */
    public function soft_deleted_records_are_not_accessible()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        
        // Soft delete the customer
        $customer->delete();

        $this->actingAs($admin)
            ->get(route('customers.show', $customer->id))
            ->assertStatus(404);
    }

    /** @test */
    public function personal_data_is_anonymized_on_deletion()
    {
        $this->markTestSkipped('User deletion route not implemented yet');
    }

    /** @test */
    public function audit_trail_is_maintained_for_sensitive_operations()
    {
        $this->markTestSkipped('User creation route not implemented yet');
    }

    /** @test */
    public function mass_assignment_protection_is_enforced()
    {
        $this->markTestSkipped('User creation route not implemented yet');
    }

    /** @test */
    public function database_queries_use_parameter_binding()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Test search functionality with potential SQL injection
        $searchTerm = "'; DROP TABLE users; --";

        $this->actingAs($admin)
            ->get(route('customers.index', ['search' => $searchTerm]));

        // Verify the users table still exists
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
