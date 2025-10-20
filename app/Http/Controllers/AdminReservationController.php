<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminReservationController extends Controller
{
    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,on_hold,confirmed,active,partial,paid,completed,cancelled',
            'payment_method' => 'nullable|in:cash,gcash,maya,card,bank',
            'amount_paid' => 'nullable|numeric|min:0',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'hours' => 'nullable|numeric|min:0',
            'pax' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $reservation->loadMissing('spaceType');

        $newStart = array_key_exists('start_time', $validated) && $validated['start_time']
            ? Carbon::parse($validated['start_time'])
            : $reservation->start_time;

        $newEnd = array_key_exists('end_time', $validated) && $validated['end_time']
            ? Carbon::parse($validated['end_time'])
            : $reservation->end_time;

        if ($newStart) {
            if ($reservation->space_id) {
                // For specific space assignments, check for any overlapping reservation
                $hasConflict = Reservation::query()
                    ->active()
                    ->where('id', '!=', $reservation->id)
                    ->where('space_id', $reservation->space_id)
                    ->overlapping($newStart, $newEnd)
                    ->exists();

                if ($hasConflict) {
                    return back()
                        ->withErrors([
                            'start_time' => 'Another reservation already occupies this space during the selected time window.',
                        ])
                        ->withInput();
                }
            } elseif ($reservation->space_type_id) {
                // For space type reservations, check available capacity considering pax and physical space count
                $spaceType = $reservation->spaceType;
                $newPax = (int) ($validated['pax'] ?? $reservation->pax ?? 1);

                if ($spaceType) {
                    $availableCapacity = $spaceType->getAvailableCapacity($newStart, $newEnd, $reservation->id);

                    if ($availableCapacity < $newPax) {
                        $message = $availableCapacity > 0
                            ? "Only {$availableCapacity} slot(s) available for this space at the selected time. You're trying to book for {$newPax} person(s)."
                            : 'This space type is fully booked for the selected time. Please adjust the schedule to continue.';

                        return back()
                            ->withErrors([
                                'start_time' => $message,
                            ])
                            ->withInput();
                    }
                } else {
                    $hasConflict = Reservation::query()
                        ->active()
                        ->where('id', '!=', $reservation->id)
                        ->where('space_type_id', $reservation->space_type_id)
                        ->overlapping($newStart, $newEnd)
                        ->exists();

                    if ($hasConflict) {
                        return back()
                            ->withErrors([
                                'start_time' => 'This space type is fully booked for the selected time. Please adjust the schedule to continue.',
                            ])
                            ->withInput();
                    }
                }
            } else {
                // No space or space type assigned, just check for any conflicts
                $hasConflict = Reservation::query()
                    ->active()
                    ->where('id', '!=', $reservation->id)
                    ->overlapping($newStart, $newEnd)
                    ->exists();
                
                if ($hasConflict) {
                    return back()
                        ->withErrors([
                            'start_time' => 'There is already another reservation covering this schedule.',
                        ])
                        ->withInput();
                }
            }
        }

        DB::transaction(function () use ($validated, $reservation) {
            $originalAmount = (float) ($reservation->amount_paid ?? 0);
            $newAmount = array_key_exists('amount_paid', $validated)
                ? (float) $validated['amount_paid']
                : $originalAmount;

            $updates = collect($validated)->except(['amount_paid'])->all();

            if (array_key_exists('start_time', $updates) && $updates['start_time']) {
                $updates['start_time'] = Carbon::parse($updates['start_time']);
            }

            if (array_key_exists('end_time', $updates) && $updates['end_time']) {
                $updates['end_time'] = Carbon::parse($updates['end_time']);
            }

            if (array_key_exists('notes', $updates) && $updates['notes']) {
                $updates['notes'] = strip_tags($updates['notes']);
            }

            $reservation->fill($updates);

            if (array_key_exists('amount_paid', $validated)) {
                $reservation->amount_paid = $newAmount;
            }

            $reservation->save();

            if ($reservation->customer && array_key_exists('amount_paid', $validated)) {
                $customer = $reservation->customer;
                $customer->amount_paid = max(0, ($customer->amount_paid ?? 0) + ($newAmount - $originalAmount));
                $customer->save();
            }
        });

        return back()->with('success', 'Reservation updated successfully.');
    }

    public function close(Request $request, Reservation $reservation)
    {
        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => 'completed',
                'end_time' => $reservation->end_time ?? Carbon::now(),
            ]);
        });

        return back()->with('success', 'Reservation closed successfully.');
    }
}
