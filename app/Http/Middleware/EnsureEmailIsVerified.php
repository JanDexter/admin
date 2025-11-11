<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $redirectToRoute = null): Response
    {
        $user = $request->user();
        
        // If no user is authenticated, redirect to login
        if (!$user) {
            return $request->expectsJson()
                    ? abort(401, 'Unauthenticated.')
                    : Redirect::guest(URL::route('login'));
        }
        
        // If user must verify email and hasn't done so, log them out
        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return $request->expectsJson()
                    ? abort(403, 'Your email address is not verified.')
                    : redirect()->route('login')->with('status', 'Please verify your email address to continue.');
        }

        return $next($request);
    }
}
