<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\SpaceType;
use App\Models\Space;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'inactive_customers' => Customer::where('status', 'inactive')->count(),
            'customer_users' => User::where('role', 'customer')->count(),
            'total_users' => User::count(),
        ];

        // Get space types with occupancy information
        $spaceTypes = SpaceType::with(['spaces' => function($query) {
            $query->with('currentCustomer');
        }])->get();

        $customers = Customer::with(['user', 'assignedSpace.spaceType'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'customers' => $customers,
            'spaceTypes' => $spaceTypes,
        ]);
    }
}
