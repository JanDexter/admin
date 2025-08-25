<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_not_available_to_public(): void
    {
        $response = $this->get('/register');

        // Registration is disabled for public users; should redirect to login with message.
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Registration is disabled. Please ask the admin to create your account.');
    }

    public function test_public_cannot_register_new_users(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Registration endpoint should redirect to login.
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Registration is disabled. Please ask the admin to create your account.');

        $this->assertGuest();
    }
}
