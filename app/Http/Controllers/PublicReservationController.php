<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicReservationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'space_type_id' => 'required|exists:space_types,id',
            'payment_method' => 'required|in:gcash,maya,cash',
            'hours' => 'nullable|integer|min:1|max:12',
            'pax' => 'nullable|integer|min:1|max:20',
            'notes' => 'nullable|string',
        ]);

        $hours = $validated['payment_method'] === 'cash'
            ? 1
            : ($validated['hours'] ?? 1);

        $reservation = Reservation::create([
            'space_type_id' => $validated['space_type_id'],
            'payment_method' => $validated['payment_method'],
            'hours' => $hours,
            'pax' => $validated['pax'] ?? 1,
            'status' => $validated['payment_method'] === 'cash' ? 'hold' : 'paid',
            'hold_until' => $validated['payment_method'] === 'cash' ? Carbon::now()->addHour() : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'reservation' => $reservation,
        ]);
    }
}
