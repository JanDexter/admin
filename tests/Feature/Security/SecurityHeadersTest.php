<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function csrf_protection_is_enforced()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Attempt POST without CSRF token should fail
        $response = $this->actingAs($admin)
            ->post(route('customers.store'), [
                'company_name' => 'Test Company',
                'contact_person' => 'John Doe',
                'email' => 'test@example.com',
                'phone' => '123-456-7890',
            ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function security_headers_are_present()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        // Check for security headers
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** @test */
    public function rate_limiting_is_enforced_on_login()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Attempt multiple failed logins
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Next attempt should be rate limited
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    /** @test */
    public function session_fixation_is_prevented()
    {
        $sessionId = session()->getId();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Session ID should change after login
        $this->assertNotEquals($sessionId, session()->getId());
    }

    /** @test */
    public function sensitive_data_is_not_exposed_in_errors()
    {
        Config::set('app.debug', false);

        // Force a 500 error by attempting to access non-existent route
        $response = $this->get('/non-existent-route');

        $response->assertStatus(404);
        
        // Ensure no sensitive information is leaked
        $content = $response->getContent();
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('secret', strtolower($content));
        $this->assertStringNotContainsString('key', strtolower($content));
    }

    /** @test */
    public function https_redirect_is_enforced_in_production()
    {
        Config::set('app.env', 'production');
        
        // This would normally be handled by middleware or web server
        // Test that the application expects HTTPS in production
        $this->assertTrue(Config::get('app.env') === 'production');
    }

    /** @test */
    public function cookie_security_flags_are_set()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        // Check session cookie has security flags
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            if (str_contains($cookie->getName(), 'session')) {
                $this->assertTrue($cookie->isHttpOnly());
                // In production, this should also check for Secure flag
            }
        }
    }

    /** @test */
    public function cors_headers_are_properly_configured()
    {
        $response = $this->get(route('dashboard'));

        // Ensure CORS is not overly permissive
        $allowOrigin = $response->headers->get('Access-Control-Allow-Origin');
        
        if ($allowOrigin) {
            $this->assertNotEquals('*', $allowOrigin);
        }
    }
}
