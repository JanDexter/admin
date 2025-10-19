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

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
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

        // Store customer info in session for reservation
        session(['google_customer' => [
            'id'     => $customer->id,
            'name'   => $customer->name,
            'email'  => $customer->email,
            'avatar' => $customer->avatar,
        ]]);

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
