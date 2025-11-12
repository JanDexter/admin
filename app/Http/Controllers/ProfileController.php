<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Customer;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Check if email or phone is changing
        $emailChanged = $user->email !== $request->email;
        $phoneChanged = $user->phone !== $request->phone;
        
        // If email is changing, require re-verification
        if ($emailChanged) {
            // Store pending email change in session for verification flow
            session(['pending_email_change' => $request->email]);
            $user->email_verified_at = null;
            
            // Send verification email to NEW address
            $user->email = $request->email;
            $user->sendEmailVerificationNotification();
            
            return back()->with('status', 'email-verification-sent');
        }
        
        // Update basic fields
        $user->fill($request->only(['name', 'phone']));
        $user->save();

        // Also update the associated Customer record if exists
        if ($user->isCustomer()) {
            $customer = Customer::where('user_id', $user->id)->first();
            if ($customer) {
                $customer->update([
                    'name' => $request->name,
                    'email' => $user->email, // Use current user email, not the request
                    'phone' => $request->phone,
                    'company_name' => $request->company_name,
                ]);
            }
        }

        // Return back with success (Inertia will handle the redirect)
        return back()->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
