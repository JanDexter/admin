<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        // Store intent (admin or customer) in session
        $intent = $request->query('intent', 'customer');
        session(['google_auth_intent' => $intent]);

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $intent = session('google_auth_intent', 'customer');
            session()->forget('google_auth_intent');

            if ($intent === 'admin') {
                return $this->handleAdminLogin($googleUser);
            } else {
                return $this->handleCustomerLogin($googleUser);
            }
        } catch (\Exception $e) {
            return redirect()->route('customer.view')
                ->with('error', 'Failed to authenticate with Google. Please try again.');
        }
    }

    /**
     * Handle admin user login/creation
     */
    protected function handleAdminLogin($googleUser)
    {
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Update Google ID if not set
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }

            // Check if user is active
            if (!$user->is_active) {
                return redirect()->route('login')
                    ->with('error', 'Your account has been deactivated. Please contact the administrator.');
            }

            // Login the user
            Auth::login($user, true);

            return $this->handleTwoFactorRedirect($user);
        }

        // New Google user - only allow if they have an existing account by email
        // This prevents random Google accounts from creating admin accounts
        return redirect()->route('login')
            ->with('error', 'No admin account found. Please use your registered email and password, or contact the administrator.');
    }

    /**
     * Handle customer login/creation for reservations
     */
    protected function handleCustomerLogin($googleUser)
    {
        $customer = Customer::where('email', $googleUser->getEmail())->first();

        if ($customer) {
            $customer->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
                'name'      => $customer->name ?: $googleUser->getName(),
            ]);
        } else {
            $displayName = $googleUser->getName() ?: trim(strtok($googleUser->getEmail(), '@'));

            $customer = Customer::create([
                'email'     => $googleUser->getEmail(),
                'name'      => $displayName,
                'company_name' => $displayName ?: 'Google Customer',
                'contact_person' => $displayName ?: 'Google Customer',
                'status'    => 'active',
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
            ]);
        }

        // Ensure there is a corresponding user account with role 'customer'
        $user = User::where('email', $customer->email)->first();
        if (!$user) {
            $user = new User([
                'name' => $customer->name,
                'email' => $customer->email,
            ]);
            $user->role = User::ROLE_CUSTOMER;
            $user->is_active = true;
            $user->google_id = $googleUser->getId();
            $user->avatar = $googleUser->getAvatar();
            // Generate a random password to satisfy not-null constraint; user signs in with Google anyway
            $user->password = bcrypt(Str::random(32));
            $user->save();
        } else {
            // Update google fields for existing user (if any)
            $user->google_id = $user->google_id ?: $googleUser->getId();
            $user->avatar = $googleUser->getAvatar() ?: $user->avatar;
            if (!$user->is_active) {
                // Don't allow deactivated users to login
                return redirect()->route('login')
                    ->with('error', 'Your account has been deactivated. Please contact the administrator.');
            }
            $user->save();
        }

        // Link customer to user if not linked
        if (!$customer->user_id) {
            $customer->user_id = $user->id;
            $customer->save();
        }

        // Log in the customer user
        Auth::login($user, true);

        return redirect()->route('customer.view');
    }

        /**
         * Handle two-factor requirements after a successful admin login.
         */
        protected function handleTwoFactorRedirect($user)
        {
            $session = session();

            if (method_exists($user, 'hasTwoFactorEnabled') && $user->hasTwoFactorEnabled()) {
                $session->put('two_factor:id', $user->id);
                $session->put('two_factor:remember', true);
                $session->put('two_factor:intended', route('dashboard'));

                Auth::logout();

                $session->flash('status', 'Please complete two-factor authentication to continue.');

                $session->regenerate();

                return redirect()->route('two-factor.challenge');
            }

            $session->regenerate();

            return redirect()->intended(route('dashboard'));
        }
}
