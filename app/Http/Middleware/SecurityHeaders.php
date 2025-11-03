<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);        // Add security headers
        // Note: X-Frame-Options set to SAMEORIGIN to allow our own iframes
        // frame-ancestors in CSP provides better control
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');// Content Security Policy for XSS protection
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.bunny.net https://maps.googleapis.com https://maps.gstatic.com https://*.googleapis.com; " .
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://maps.gstatic.com; " .
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:; " .
            "img-src 'self' data: https: blob: https://maps.googleapis.com https://maps.gstatic.com https://*.googleapis.com https://*.gstatic.com; " .
            "connect-src 'self' https://maps.googleapis.com https://*.googleapis.com; " .
            "frame-src 'self' https://www.google.com https://maps.google.com https://www.google.com/maps/; " .
            "child-src 'self' https://www.google.com https://maps.google.com; " .
            "frame-ancestors 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self';"
        );

        // Permissions Policy (formerly Feature-Policy)
        $response->headers->set('Permissions-Policy', 
            "geolocation=(), microphone=(), camera=()"
        );

        return $response;
    }
}