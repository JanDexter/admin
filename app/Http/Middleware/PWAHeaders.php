<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PWAHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add PWA-related headers
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow', false);
        
        // Add Cache-Control headers for service worker
        if ($request->is('sw.js')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        // Add manifest headers
        if ($request->is('manifest.json')) {
            $response->headers->set('Content-Type', 'application/manifest+json');
        }

        return $response;
    }
}
