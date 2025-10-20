<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionsExport;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'daily');

        $query = Reservation::query()
            ->with(['customer', 'space.spaceType', 'spaceType'])
            ->orderByDesc('created_at');

        $this->applyDateFilter($query, $filter);

    $transactions = (clone $query)->paginate(20)->through(fn ($reservation) => [
            'id' => $reservation->id,
            'created_at' => $reservation->created_at,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'hours' => $reservation->hours,
            'pax' => $reservation->pax,
            'customer' => $reservation->customer ? [
                'id' => $reservation->customer->id,
                'name' => $reservation->customer->name ?? $reservation->customer->company_name,
            ] : null,
            'space' => $reservation->space ? [
                'id' => $reservation->space->id,
                'name' => $reservation->space->name,
                'space_type' => $reservation->space->spaceType ? [
                    'id' => $reservation->space->spaceType->id,
                    'name' => $reservation->space->spaceType->name,
                ] : null,
            ] : null,
            'space_type' => $reservation->spaceType ? [
                'id' => $reservation->spaceType->id,
                'name' => $reservation->spaceType->name,
            ] : null,
            'total_cost' => $reservation->total_cost,
            'amount_paid' => $reservation->amount_paid ?? 0,
            'payment_method' => $reservation->payment_method,
            'status' => $reservation->status,
            'is_discounted' => $reservation->is_discounted,
            'notes' => $reservation->notes,
        ]);

    $revenueTotal = (clone $query)
            ->paidOrCompleted()
            ->get()
            ->sum(fn ($reservation) => $reservation->total_cost);

        return Inertia::render('Accounting/Index', [
            'transactions' => $transactions,
            'filters' => ['filter' => $filter],
            'summary' => [
                'totalRevenue' => round($revenueTotal, 2),
                'transactionCount' => $transactions->total(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $filter = $request->input('filter', 'daily');
        $filename = sprintf('transactions-%s-%s.xlsx', $filter, now()->format('Ymd_His'));

        return Excel::download(new TransactionsExport($filter), $filename);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,partial,paid,completed,cancelled',
            'payment_method' => 'nullable|in:cash,gcash,maya,bank_transfer',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Update the reservation
        $reservation->update($validated);

        return back()->with('success', 'Transaction updated successfully.');
    }

    protected function applyDateFilter($query, string $filter): void
    {
        switch ($filter) {
            case 'weekly':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'monthly':
                $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                break;
            case 'daily':
            default:
                $query->whereDate('created_at', Carbon::today());
                break;
        }
    }
}
