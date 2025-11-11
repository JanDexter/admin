<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'is_admin' => $user->isAdmin(),
                    'is_staff' => $user->isStaff(),
                    'is_customer' => $user->isCustomer(),
                    'role_type' => $user->isAdmin() ? 'admin' : ($user->isStaff() ? 'staff' : 'customer'),
                ]) : null,
                'can' => [
                    'admin_access' => $user ? $user->can('admin-access') : false,
                    'manage_users' => $user ? $user->can('manage-users') : false,
                ],
            ],
        ];
    }
}
