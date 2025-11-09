<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\EmailVerificationOtp;
use App\Mail\VerificationOtpMail;
use Illuminate\Auth\Events\Registered;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Check if the user exists and is active before attempting auth
        $user = User::where('email', $this->string('email'))
            ->first();

        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            // Verify password first before proceeding
            if (! Hash::check($this->string('password'), $user->password)) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }

            // Check if email is verified
            if (! $user->hasVerifiedEmail()) {
                // New account that hasn't verified email yet - log them in temporarily
                Auth::login($user, $this->boolean('remember'));
                
                // Generate and send OTP
                $otp = EmailVerificationOtp::generateForUser($user);
                Mail::to($user->email)->send(new VerificationOtpMail($user, $otp));
                
                RateLimiter::clear($this->throttleKey());
                
                // This will be caught by the controller to redirect to verification notice
                throw ValidationException::withMessages([
                    'unverified' => 'Please verify your email address to continue.',
                ]);
            }

            // Account was deactivated but email is verified - allow reactivation
            // Log them in temporarily and send reactivation OTP
            Auth::login($user, $this->boolean('remember'));
            
            // Generate and send OTP for reactivation
            $otp = EmailVerificationOtp::generateForUser($user);
            Mail::to($user->email)->send(new VerificationOtpMail($user, $otp));
            
            RateLimiter::clear($this->throttleKey());
            
            // This will be caught by the controller to redirect to verification notice
            throw ValidationException::withMessages([
                'reactivation' => 'Your account has been deactivated. We\'ve sent a verification code to reactivate it.',
            ]);
        }

        // No role restriction here - customers can log in through /login, admins through /coz-control-access
        // The controller will handle the redirect based on role

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
