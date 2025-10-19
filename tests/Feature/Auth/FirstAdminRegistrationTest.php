<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstAdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function registration_page_is_available_when_no_users_exist()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    /** @test */
    public function first_registered_user_becomes_an_admin()
    {
        $this->assertDatabaseCount('users', 0);

        $response = $this->post('/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

                $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function registration_page_is_disabled_after_first_user_is_created()
    {
        // Create the first user
        User::factory()->create();
        $this->assertDatabaseCount('users', 1);

        // Attempt to access the registration page again
        $response = $this->get('/register');
        $response->assertRedirect(route('login'))
                 ->assertSessionHas('status', 'Registration is disabled. Please ask the admin to create your account.');
    }

    /** @test */
    public function registration_attempt_is_blocked_if_a_user_already_exists()
    {
        // Create a user first
        User::factory()->create();

        // Attempt to register a new user
        $response = $this->post('/register', [
            'name' => 'Another User',
            'email' => 'another@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('users', 1); // Ensure no new user was created
    }
}
