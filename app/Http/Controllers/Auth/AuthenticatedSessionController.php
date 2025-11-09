<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        // Registration remains open for customers; controller assigns roles safely
        $canRegister = true;
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'canRegister' => $canRegister,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Check if this is an unverified account
            if (isset($e->errors()['unverified'])) {
                // User is logged in but needs to verify email
                return redirect()->route('customer.verification.notice')
                    ->with('status', 'verification-link-sent');
            }
            
            // Check if this is a reactivation request
            if (isset($e->errors()['reactivation'])) {
                // User is logged in but needs to verify email to reactivate
                return redirect()->route('customer.verification.notice')
                    ->with('status', 'reactivation-requested');
            }
            // Re-throw other validation exceptions
            throw $e;
        }

    session()->regenerate();

        // Redirect based on role to prevent customers from entering the admin area
        $user = Auth::user();
        if ($user && $user->isAdmin()) {
            return redirect()->route('dashboard');
        }
        return redirect()->route('customer.view');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
