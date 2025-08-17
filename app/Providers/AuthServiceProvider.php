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

        // Define gate for user management - only admin can manage users
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        // Define gate for admin level access
        Gate::define('admin-access', function (User $user) {
            return $user->isAdmin();
        });

        // Define gate for customer access (admin can also access customer features)
        Gate::define('customer-access', function (User $user) {
            return $user->isAdmin() || $user->isCustomer();
        });
    }
}
