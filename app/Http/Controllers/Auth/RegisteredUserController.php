<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\EmailVerificationOtp;
use App\Mail\VerificationOtpMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response|RedirectResponse
    {
        // Registration should be open for customers at all times.
        // Only the very first user becomes admin; subsequent signups are customers.
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'nullable|string|max:20',
        ]);

        $isFirstUser = User::count() === 0;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password, // Use the validated password directly
            'role' => $isFirstUser ? User::ROLE_ADMIN : User::ROLE_CUSTOMER,
            'is_active' => $isFirstUser ? true : false, // First user (admin) is active, others must verify email
        ]);

        // Automatically create a Customer record for customer users
        if ($user->role === User::ROLE_CUSTOMER) {
            Customer::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => 'pending', // Set to pending until email verified
            ]);
        }

        event(new Registered($user));

        // First user (admin) logs in automatically, others must verify email
        if ($isFirstUser) {
            Auth::login($user);
            return redirect()->route('dashboard');
        }

        // For customers, generate and send OTP
        $otp = EmailVerificationOtp::generateForUser($user);
        Mail::to($user->email)->send(new VerificationOtpMail($user, $otp));

        // Log them in temporarily to show verification notice
        // They won't be able to access protected routes until verified
        Auth::login($user);
        return redirect()->route('customer.verification.notice');
    }
}
