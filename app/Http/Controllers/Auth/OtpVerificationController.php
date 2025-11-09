<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationOtp;
use App\Models\Customer;
use App\Mail\VerificationOtpMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Verified;
use Inertia\Inertia;

class OtpVerificationController extends Controller
{
    /**
     * Verify the OTP
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        if ($user->hasVerifiedEmail()) {
            // Already verified, redirect to appropriate dashboard
            if ($user->isAdmin()) {
                return redirect()->route('dashboard');
            }
            return redirect()->route('customer.view');
        }

        // Verify OTP
        if (!EmailVerificationOtp::verifyForUser($user, $request->otp)) {
            return back()->withErrors([
                'otp' => 'Invalid or expired OTP. Please try again or request a new code.'
            ]);
        }

        // Mark email as verified
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
            return redirect()->route('dashboard')->with('status', 'Email verified successfully!');
        }
        return redirect()->route('customer.view')->with('status', 'Email verified successfully!');
    }

    /**
     * Resend OTP
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        if ($user->hasVerifiedEmail()) {
            // Already verified
            if ($user->isAdmin()) {
                return redirect()->route('dashboard');
            }
            return redirect()->route('customer.view');
        }

        // Generate and send new OTP
        $otp = EmailVerificationOtp::generateForUser($user);
        Mail::to($user->email)->send(new VerificationOtpMail($user, $otp));

        return back()->with('status', 'A new verification code has been sent to your email.');
    }
}
