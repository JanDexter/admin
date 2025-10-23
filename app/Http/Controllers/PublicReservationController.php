<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\SpaceType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicReservationController extends Controller
{
    /**
     * Allow start times that are effectively "now", with a small grace window for rounding.
     */
    protected function assertStartTimeIsNotTooFarInThePast(Carbon $startTime): void
    {
        $graceWindow = Carbon::now(config('app.timezone'))
            ->subMinutes(1);

        if ($startTime->lt($graceWindow)) {
            throw ValidationException::withMessages([
                'start_time' => 'The start time must be a date after or equal to now.',
            ]);
        }
    }

    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'hours' => 'required|integer|min:1|max:12',
            'pax' => 'nullable|integer|min:1|max:20',
        ]);

        $startTime = Carbon::parse($validated['start_time'], config('app.timezone'));
        $this->assertStartTimeIsNotTooFarInThePast($startTime);
        $endTime = (clone $startTime)->addHours($validated['hours']);
        $requestedPax = $validated['pax'] ?? 1;

        $spaceTypes = SpaceType::all();
        $availability = [];

        foreach ($spaceTypes as $spaceType) {
            $availableCapacity = $spaceType->getAvailableCapacity($startTime, $endTime);
            $canAccommodate = $availableCapacity >= $requestedPax;
            
            $availability[] = [
                'id' => $spaceType->id,
                'name' => $spaceType->name,
                'total_slots' => $spaceType->total_slots,
                'available_capacity' => $availableCapacity,
                'requested_pax' => $requestedPax,
                'can_accommodate' => $canAccommodate,
                'is_available' => $canAccommodate,
            ];
        }

        return response()->json([
            'availability' => $availability,
            'start_time' => $startTime->toIso8601String(),
            'end_time' => $endTime->toIso8601String(),
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'space_type_id' => 'required|exists:space_types,id',
            'payment_method' => 'required|in:gcash,maya,cash',
            'hours' => 'nullable|integer|min:1|max:12',
            'pax' => 'nullable|integer|min:1|max:20',
            'notes' => 'nullable|string',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_company_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date',
        ]);

        $spaceType = SpaceType::findOrFail($validated['space_type_id']);

        $hours = $validated['payment_method'] === 'cash'
            ? 1
            : ($validated['hours'] ?? 1);

        $pax = $validated['pax'] ?? 1;

        $startTime = isset($validated['start_time']) 
            ? Carbon::parse($validated['start_time'], config('app.timezone')) 
            : Carbon::now(config('app.timezone'));
        $this->assertStartTimeIsNotTooFarInThePast($startTime);
        $endTime = (clone $startTime)->addHours($hours);

    // Check available capacity using the same logic as the availability check endpoint
    $availableCapacity = $spaceType->getAvailableCapacity($startTime, $endTime);

        // Check if there's enough capacity for this booking
        if ($availableCapacity < $pax) {
            $message = $availableCapacity > 0
                ? "Only {$availableCapacity} slot(s) available for this space at the selected time. You're trying to book for {$pax} person(s)."
                : 'This space is fully booked for the requested time. Please try a different time or choose another space.';
            
            return redirect()
                ->back()
                ->withErrors([
                    'space_type_id' => $message,
                ])
                ->withInput();
        }

        $appliedHourlyRate = $spaceType->hourly_rate ?? $spaceType->default_price ?? 0;
        $appliedDiscountHours = $spaceType->default_discount_hours;
        $appliedDiscountPercentage = $spaceType->default_discount_percentage;
        $isDiscounted = $appliedDiscountHours && $hours >= $appliedDiscountHours;

        $baseCost = $hours * $appliedHourlyRate;
        if ($isDiscounted && $appliedDiscountPercentage) {
            $baseCost -= ($baseCost * ($appliedDiscountPercentage / 100));
        }

    $reservation = DB::transaction(function () use ($validated, $spaceType, $hours, $pax, $appliedHourlyRate, $appliedDiscountHours, $appliedDiscountPercentage, $isDiscounted, $baseCost, $startTime, $endTime) {
            // Find or create the customer
            $customer = Customer::firstOrCreate(
                ['email' => $validated['customer_email']],
                [
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'company_name' => $validated['customer_company_name'] ?? null,
                    'contact_person' => $validated['customer_name'],
                    'space_type_id' => $spaceType->id,
                    'user_id' => Auth::id(), // Link to user if logged in
                ]
            );

            // If the customer already existed, update their contact info when new details are provided
            if (!$customer->wasRecentlyCreated) {
                $customer->fill([
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'space_type_id' => $spaceType->id,
                ]);

                if (!empty($validated['customer_company_name'])) {
                    $customer->company_name = $validated['customer_company_name'];
                }

                if (empty($customer->contact_person)) {
                    $customer->contact_person = $validated['customer_name'];
                }

                if (Auth::id() && !$customer->user_id) {
                    $customer->user_id = Auth::id();
                }

                $customer->save();
            }

            // Auto-assign to an available physical space without time conflicts
            $assignedSpace = \App\Models\Space::where('space_type_id', $spaceType->id)
                ->whereDoesntHave('reservations', function($q) use ($startTime, $endTime) {
                    // Check for overlapping reservations
                    $q->active()
                      ->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->first();

            $spaceId = $assignedSpace ? $assignedSpace->id : null;

            // Create the reservation
            $reservation = Reservation::create([
                'user_id' => Auth::id(), // Can be null if not logged in
                'customer_id' => $customer->id,
                'space_id' => $spaceId, // Assign to physical space if available
                'space_type_id' => $spaceType->id,
                'payment_method' => $validated['payment_method'],
                'hours' => $hours,
                'pax' => $pax,
                'status' => $validated['payment_method'] === 'cash' ? 'hold' : 'paid',
                'hold_until' => $validated['payment_method'] === 'cash' ? Carbon::now()->addHour() : null,
                'notes' => $validated['notes'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'applied_hourly_rate' => $appliedHourlyRate,
                'applied_discount_hours' => $appliedDiscountHours,
                'applied_discount_percentage' => $appliedDiscountPercentage,
                'is_discounted' => $isDiscounted,
                'cost' => round($baseCost, 2),
            ]);

            return $reservation;
        });

        return redirect()
            ->route('customer.view')
            ->with('reservationCreated', [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'total_cost' => $reservation->total_cost,
            ]);
    }

    public function extend(Request $request, Reservation $reservation)
    {
        // Ensure the user owns this reservation
        if ($reservation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'hours' => 'required|integer|min:1|max:12',
        ]);

        $extensionHours = $validated['hours'];
        $hourlyRate = $reservation->effective_hourly_rate;
        $extensionCost = $extensionHours * $hourlyRate;

        DB::transaction(function () use ($reservation, $extensionHours, $extensionCost) {
            // Extend the end time
            $reservation->end_time = Carbon::parse($reservation->end_time)->addHours($extensionHours);
            $reservation->hours += $extensionHours;
            
            // Update the cost
            $currentCost = $reservation->cost ?? $reservation->total_cost;
            $reservation->cost = round($currentCost + $extensionCost, 2);
            
            // If it was fully paid, mark as partial since they owe more now
            if ($reservation->status === 'paid' || $reservation->status === 'completed') {
                $reservation->status = 'partial';
            }
            
            $reservation->save();
        });

        return redirect()
            ->back()
            ->with('success', "Reservation extended by {$extensionHours} hour(s). Additional cost: " . number_format($extensionCost, 2));
    }

    public function endEarly(Request $request, Reservation $reservation)
    {
        // Ensure the user owns this reservation
        if ($reservation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Only allow ending active reservations early
        if ($reservation->status !== 'active') {
            return redirect()
                ->back()
                ->with('error', 'Only active reservations can be ended early.');
        }

        $now = Carbon::now();
        $originalEnd = Carbon::parse($reservation->end_time);
        
        // Check if already past end time
        if ($now->gte($originalEnd)) {
            return redirect()
                ->back()
                ->with('error', 'Reservation has already ended.');
        }

        DB::transaction(function () use ($reservation, $now) {
            $originalStart = Carbon::parse($reservation->start_time);
            $originalEnd = Carbon::parse($reservation->end_time);
            $actualHoursUsed = $now->diffInMinutes($originalStart) / 60;
            
            // Calculate refund (if any)
            $hourlyRate = $reservation->effective_hourly_rate;
            $originalCost = $reservation->total_cost;
            $actualCost = ceil($actualHoursUsed) * $hourlyRate;
            $refundAmount = max(0, $originalCost - $actualCost);
            
            // Update reservation
            $reservation->end_time = $now;
            $reservation->hours = ceil($actualHoursUsed);
            $reservation->cost = round($actualCost, 2);
            $reservation->status = 'completed';
            $reservation->notes = ($reservation->notes ?? '') . "\nEnded early. Refund: ₱" . number_format($refundAmount, 2);
            $reservation->save();
            
            // Adjust amount_paid if there was overpayment
            if ($reservation->amount_paid > $actualCost) {
                $reservation->amount_paid = $actualCost;
                $reservation->save();
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Reservation ended early. Any applicable refund will be processed.');
    }

    public function destroy(Reservation $reservation)
    {
        // Ensure the user owns this reservation
        if ($reservation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Only allow canceling pending, on_hold, or confirmed reservations
        if (!in_array($reservation->status, ['pending', 'on_hold', 'confirmed', 'hold', 'paid'])) {
            return redirect()
                ->back()
                ->with('error', 'Only pending, on-hold, or active reservations can be cancelled.');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->status = 'cancelled';
            $reservation->save();
        });

        return redirect()
            ->back()
            ->with('success', 'Reservation cancelled successfully.');
    }
}

