<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            // Redirect based on role
            if ($user->isAdmin()) {
                return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
            }
            return redirect()->intended(route('customer.view', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            // Activate the user account
            $user->is_active = true;
            $user->save();

            // If customer, update their status to active
            if ($user->role === \App\Models\User::ROLE_CUSTOMER) {
                $customer = Customer::where('user_id', $user->id)->first();
                if ($customer) {
                    $customer->status = 'active';
                    $customer->save();
                }
            }
        }

        // Redirect based on role
        if ($user->isAdmin()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }
        return redirect()->intended(route('customer.view', absolute: false).'?verified=1');
    }
}
