<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'daily'); // 'daily', 'weekly', 'monthly'

        $query = Reservation::query()
            ->with('customer', 'space.spaceType');

        $dateColumn = 'created_at';

        switch ($filter) {
            case 'weekly':
                $query->whereBetween($dateColumn, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'monthly':
                $query->whereBetween($dateColumn, [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                break;
            case 'daily':
            default:
                $query->whereDate($dateColumn, Carbon::today());
                break;
        }

        $transactions = $query->latest()->paginate(20);

        // Clone the query to calculate summary without pagination constraints
        $summaryQuery = clone $query;
        $totalRevenue = $summaryQuery->whereIn('status', ['paid', 'completed'])->sum('total_cost');

        return Inertia::render('Accounting/Index', [
            'transactions' => $transactions,
            'filters' => ['filter' => $filter],
            'summary' => [
                'totalRevenue' => $totalRevenue,
                'transactionCount' => $transactions->total(),
            ],
        ]);
    }
}
