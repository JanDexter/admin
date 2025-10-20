<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\SpaceType;
use App\Models\Space;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
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

        // Get recent transactions (completed reservations)
        $recentTransactions = Reservation::with(['customer', 'space.spaceType'])
            ->whereIn('status', ['completed', 'paid'])
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->limit(10)
            ->get()
            ->map(function($reservation) {
                // For open-time, always use the saved cost (should be set on end)
                $cost = $reservation->is_open_time ? $reservation->cost : ($reservation->cost ?? $reservation->total_cost);
                return [
                    'id' => $reservation->id,
                    'customer_name' => $reservation->customer->company_name ?? $reservation->customer->name ?? 'N/A',
                    'space_name' => $reservation->space->name ?? 'N/A',
                    'space_type' => $reservation->space->spaceType->name ?? 'N/A',
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'cost' => $cost,
                    'status' => $reservation->status,
                ];
            });

        // Get active services (ongoing reservations)
        $activeServices = Reservation::with(['customer', 'space.spaceType'])
            ->whereIn('status', ['active'])
            ->whereNull('end_time')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function($reservation) {
                return [
                    'id' => $reservation->id,
                    'customer_name' => $reservation->customer->company_name ?? $reservation->customer->name ?? 'N/A',
                    'space_name' => $reservation->space->name ?? 'N/A',
                    'space_type' => $reservation->space->spaceType->name ?? 'N/A',
                    'start_time' => $reservation->start_time,
                    'hourly_rate' => $reservation->effective_hourly_rate,
                    'is_open_time' => $reservation->is_open_time,
                ];
            });

        // Sorting params
        $sortBy = $request->query('sort_by', 'date');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Customer::query()
            ->with(['user', 'assignedSpace.spaceType'])
            ->leftJoin('spaces', 'spaces.current_customer_id', '=', 'customers.id')
            ->leftJoin('space_types', 'space_types.id', '=', 'spaces.space_type_id')
            ->select('customers.*')
            ->addSelect(DB::raw('COALESCE(NULLIF(customers.name, ""), NULLIF(customers.company_name, ""), NULLIF(customers.contact_person, ""), customers.email) AS sort_name'))
            ->addSelect(DB::raw('space_types.name AS assigned_space_type'));

        switch ($sortBy) {
            case 'name':
                $query->orderBy('sort_name', $sortDir);
                break;
            case 'space_type':
                $query->orderBy('assigned_space_type', $sortDir);
                break;
            case 'status':
                $query->orderBy('customers.status', $sortDir);
                break;
            case 'date':
            default:
                $query->orderBy('customers.created_at', $sortDir);
                // tie-breaker by status
                $query->orderBy('customers.status', 'asc');
                $sortBy = 'date'; // normalize
                break;
        }

        $customers = $query->paginate(10)->through(function ($customer) {
            // Calculate running amount due for ongoing reservation (no end time)
            $assignedSpace = $customer->assignedSpace; // eager loaded
            if ($assignedSpace) {
                $openRes = Reservation::where('customer_id', $customer->id)
                    ->where('space_id', $assignedSpace->id)
                    ->whereNull('end_time')
                    ->latest()
                    ->first();
                if ($openRes) {
                    $hours = now()->diffInHours($openRes->start_time);
                    $hourly = $openRes->custom_hourly_rate ?? $openRes->applied_hourly_rate;
                    $runningCost = $assignedSpace->calculateCost($hours, $hourly, $openRes->applied_discount_hours, $openRes->applied_discount_percentage);
                    // You may subtract payments here if tracking per-reservation payments
                    $customer->setAttribute('amount_due', $runningCost);
                }
            }
            return $customer;
        });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'customers' => $customers,
            'spaceTypes' => $spaceTypes,
            'recentTransactions' => $recentTransactions,
            'activeServices' => $activeServices,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
        ]);
    }
}
