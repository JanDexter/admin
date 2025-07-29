<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gate for user management
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        // Define gate for staff level access
        Gate::define('staff-access', function (User $user) {
            return $user->isAdmin() || $user->isStaff();
        });

        // Define gate for customer access
        Gate::define('customer-access', function (User $user) {
            return $user->isCustomer();
        });
    }
}
