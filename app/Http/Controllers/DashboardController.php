<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
        ];

        $customers = Customer::orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'customers' => $customers,
        ]);
    }
}
