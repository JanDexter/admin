<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class EnsureAdminExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for setup routes, login, and logout
        if ($request->is('setup*') || $request->is('login') || $request->is('logout')) {
            return $next($request);
        }
        
        // Check if any admin user exists
        if (User::whereHas('admin')->count() === 0) {
            return redirect('/setup');
        }
        
        return $next($request);
    }
}
