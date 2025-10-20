<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $events = Reservation::with(['customer', 'space.spaceType'])->get()->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'title' => $reservation->space->name ?? 'Reservation',
                'start' => $reservation->start_time,
                'end' => $reservation->end_time,
                'allDay' => $reservation->is_open_time,
                'extendedProps' => [
                    'customerName' => $reservation->customer->name ?? 'N/A',
                    'spaceName' => $reservation->space->name ?? 'N/A',
                    'spaceTypeName' => $reservation->space->spaceType->name ?? 'N/A',
                    'status' => $reservation->status,
                    'totalCost' => $reservation->total_cost,
                    'paymentMethod' => $reservation->payment_method,
                    'is_open_time' => $reservation->is_open_time,
                ],
                'backgroundColor' => $this->getEventColor($reservation->status),
                'borderColor' => $this->getEventColor($reservation->status),
            ];
        });

        return Inertia::render('Calendar/Index', [
            'events' => $events,
        ]);
    }

    private function getEventColor($status)
    {
        switch ($status) {
            case 'paid':
            case 'completed':
                return '#10B981'; // Emerald 500
            case 'active':
                return '#3B82F6'; // Blue 500
            case 'hold':
                return '#F59E0B'; // Amber 500
            case 'cancelled':
                return '#EF4444'; // Red 500
            default:
                return '#6B7280'; // Gray 500
        }
    }
}
