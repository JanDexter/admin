<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockSocialiteUser($email, $name = 'Test User', $id = '12345')
    {
        $socialiteUser = Mockery::mock('Laravel\Socialite\Two\User');
        $socialiteUser
            ->shouldReceive('getId')->andReturn($id)
            ->shouldReceive('getName')->andReturn($name)
            ->shouldReceive('getEmail')->andReturn($email)
            ->shouldReceive('getAvatar')->andReturn('http://example.com/avatar.jpg');
        
        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    /** @test */
    public function it_redirects_to_google_for_admin_login()
    {
        $response = $this->get(route('auth.google', ['intent' => 'admin']));
        $response->assertRedirect();
        $this->assertStringContainsString('https://accounts.google.com/o/oauth2/auth', $response->getTargetUrl());
    }

    /** @test */
    public function it_allows_an_existing_admin_to_log_in()
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);

        $this->mockSocialiteUser('admin@example.com');
        
        $response = $this->withSession(['google_auth_intent' => 'admin'])->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    /** @test */
    public function it_blocks_a_non_existent_user_from_admin_login()
    {
        $this->mockSocialiteUser('new-user@example.com');

        $response = $this->withSession(['google_auth_intent' => 'admin'])
                         ->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'))
                 ->assertSessionHas('error', 'No admin account found. Please use your registered email and password, or contact the administrator.');
        $this->assertGuest();
    }

    /** @test */
    public function it_creates_a_new_customer_and_stores_in_session_on_customer_login()
    {
        $this->mockSocialiteUser('customer@example.com', 'Customer Name');

        $response = $this->withSession(['google_auth_intent' => 'customer'])
                         ->get(route('auth.google.callback'));

        $response->assertRedirect(route('customer.view'));
        $this->assertDatabaseHas('customers', [
            'email' => 'customer@example.com',
            'name' => 'Customer Name',
            'google_id' => '12345',
        ]);

        $this->assertEquals('Customer Name', session('google_customer.name'));
    }
}
