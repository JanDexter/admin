<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class PasswordChangeController extends Controller
{
    /**
     * Request a password change - sends verification email
     */
    public function requestChange(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return back()->with('error', 'Unauthorized');
        }

        // Generate a secure token
        $token = Str::random(64);

        // Store the token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send email with verification link
        $verificationUrl = route('password.change.verify', ['token' => $token, 'email' => $user->email]);

        try {
            Mail::send('emails.password-change-verification', [
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Verify Your Password Change Request');
            });

            return back()->with('success', 'Verification email sent successfully. Please check your inbox.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send verification email. Please try again.');
        }
    }

    /**
     * Verify the token and show password change form
     */
    public function verifyToken(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (!$email || !$token) {
            return Inertia::render('Auth/PasswordChange', [
                'status' => 'invalid',
                'message' => 'Invalid or missing verification link.',
            ]);
        }

        // Find the token in the database
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$passwordReset) {
            return Inertia::render('Auth/PasswordChange', [
                'status' => 'invalid',
                'message' => 'Invalid or expired verification link.',
            ]);
        }

        // Check if token matches and is not expired (valid for 1 hour)
        if (!Hash::check($token, $passwordReset->token)) {
            return Inertia::render('Auth/PasswordChange', [
                'status' => 'invalid',
                'message' => 'Invalid verification token.',
            ]);
        }

        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            return Inertia::render('Auth/PasswordChange', [
                'status' => 'expired',
                'message' => 'Verification link has expired. Please request a new one.',
            ]);
        }

        return Inertia::render('Auth/PasswordChange', [
            'status' => 'valid',
            'email' => $email,
            'token' => $token,
        ]);
    }

    /**
     * Update the password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Verify token again
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
            return back()->withErrors(['email' => 'Invalid or expired token.']);
        }

        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            return back()->withErrors(['email' => 'Verification link has expired.']);
        }

        // Update the user's password
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return redirect()->route('customer.view')->with('success', 'Password changed successfully! Please log in with your new password.');
    }
}
