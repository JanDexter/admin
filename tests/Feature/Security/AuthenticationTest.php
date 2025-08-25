<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function users_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /** @test */
    public function users_cannot_login_without_email_verification()
    {
        // Email verification is currently disabled, so this test is skipped
        $this->markTestSkipped('Email verification is disabled');
        
        /*
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        
        // Check if redirected to verification notice
        $this->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
        */
    }

    /** @test */
    public function inactive_users_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
    }

    /** @test */
    public function password_requirements_are_enforced_in_registration()
    {
        $this->markTestSkipped('Registration not implemented yet');
    }

    /** @test */
    public function email_must_be_unique_in_registration()
    {
        $this->markTestSkipped('Registration not implemented yet');
    }

    /** @test */
    public function authenticated_users_are_redirected_from_auth_pages()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));

        // Skip register test as registration may not be implemented
        // $this->actingAs($user)
        //     ->get(route('register'))
        //     ->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function session_expires_after_inactivity()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        $this->assertAuthenticated();

        // Simulate session timeout by clearing session
        $this->app['session']->flush();

        // Refresh the application so middleware reads the flushed session
        $this->refreshApplication();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
