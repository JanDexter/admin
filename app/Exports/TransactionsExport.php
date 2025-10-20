<?php

namespace App\Exports;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly string $filter = 'daily') {}

    public function collection(): Collection
    {
        $query = Reservation::with(['customer', 'space.spaceType', 'spaceType'])->orderByDesc('created_at');

        $this->applyDateFilter($query, $this->filter);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Customer',
            'Space Type',
            'Space',
            'Hours',
            'Payment Method',
            'Status',
            'Discounted',
            'Total Cost',
        ];
    }

    public function map($reservation): array
    {
        return [
            optional($reservation->created_at)->format('Y-m-d H:i'),
            $reservation->customer?->name ?? $reservation->customer?->company_name ?? 'N/A',
            $reservation->spaceType?->name ?? $reservation->space?->spaceType?->name ?? 'N/A',
            $reservation->space?->name ?? 'N/A',
            $reservation->hours,
            strtoupper($reservation->payment_method ?? 'n/a'),
            $reservation->status,
            $reservation->is_discounted ? 'Yes' : 'No',
            number_format($reservation->total_cost, 2),
        ];
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
