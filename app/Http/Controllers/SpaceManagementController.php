<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Customer;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SpaceManagementController extends Controller
{
    public function index()
    {
        $spaceTypes = SpaceType::with(['spaces' => function($query) {
            $query->with('currentCustomer');
        }])->get();

        // Select only guaranteed columns; compute display name in the client
        $customers = Customer::where('status', 'active')
            ->select('id', 'name', 'company_name', 'contact_person', 'email', 'phone')
            ->orderByRaw("
                COALESCE(
                    NULLIF(name, ''),
                    NULLIF(company_name, ''),
                    NULLIF(contact_person, ''),
                    email
                ) ASC
            ")
            ->get();

        return Inertia::render('SpaceManagement/Index', [
            'spaceTypes' => $spaceTypes,
            'customers' => $customers,
        ]);
    }

    public function updatePricing(Request $request, SpaceType $spaceType)
    {
        $validated = $request->validate([
            'hourly_rate' => 'nullable|numeric|min:0',
            'default_price' => 'nullable|numeric|min:0',
            'default_discount_hours' => 'nullable|integer|min:1',
            'default_discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        // If only default_price provided and no hourly_rate, mirror to hourly_rate for consistency
        if (isset($validated['default_price']) && !isset($validated['hourly_rate'])) {
            $validated['hourly_rate'] = $validated['default_price'];
        }

        $spaceType->update($validated);

        return redirect()->back()->with('success', "Pricing for {$spaceType->name} updated successfully.");
    }

    public function releaseSpace(Space $space)
    {
        if ($space->status === 'occupied') {
            $space->release();
            return redirect()->back()->with('success', "Space {$space->name} has been released.");
        }

        return redirect()->back()->with('error', 'Space is not currently occupied.');
    }

    public function assignSpace(Request $request, Space $space)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'occupied_until' => 'nullable|date|after:now',
            'custom_hourly_rate' => 'nullable|numeric|min:0',
            'start_time' => 'nullable|date|after_or_equal:now',
        ]);

        if (!$space->isAvailable()) {
            return redirect()->back()->with('error', 'Space is not available.');
        }

        $from = $validated['start_time'] ?? now();
        $until = $validated['occupied_until'] ?? null;

        $space->occupy(
            $validated['customer_id'],
            $from,
            $until
        );

        // Update latest reservation for this space to include custom rate (if provided)
        $reservation = \App\Models\Reservation::where('space_id', $space->id)
            ->where('customer_id', $validated['customer_id'])
            ->latest()
            ->first();
        if ($reservation) {
            if (isset($validated['custom_hourly_rate'])) {
                $reservation->update(['custom_hourly_rate' => $validated['custom_hourly_rate']]);
            }
            // If end time provided at assignment, compute an initial cost estimate using snapshot values
            if ($until) {
                $hours = max(0, \Carbon\Carbon::parse($from)->diffInHours(\Carbon\Carbon::parse($until)));
                $hourly = $reservation->custom_hourly_rate ?? $reservation->applied_hourly_rate;
                $estimated = $space->calculateCost($hours, $hourly, $reservation->applied_discount_hours, $reservation->applied_discount_percentage);
                $reservation->update(['cost' => $estimated]);
            }
        }

        return redirect()->back()->with('success', "Space {$space->name} has been assigned.");
    }

    public function startOpenTime(Request $request, Space $space)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        if ($space->status !== 'available') {
            return back()->with('error', 'Space is not available.');
        }

        DB::transaction(function () use ($request, $space) {
            $space->update([
                'status' => 'occupied',
                'current_customer_id' => $request->customer_id,
                'occupied_from' => Carbon::now(),
                'occupied_until' => null, // Open time
            ]);

            Reservation::create([
                'user_id' => auth()->id(),
                'customer_id' => $request->customer_id,
                'space_id' => $space->id,
                'start_time' => $space->occupied_from,
                'end_time' => null,
                'hourly_rate' => $space->spaceType->price_per_hour,
                'status' => 'active',
                'is_open_time' => true,
            ]);

            $space->spaceType->decrement('available_slots');
        });

        return back()->with('success', 'Open time started successfully.');
    }

    public function endOpenTime(Space $space)
    {
        $reservation = Reservation::where('space_id', $space->id)
            ->where('is_open_time', true)
            ->whereNull('end_time')
            ->first();

        if (!$reservation) {
            return back()->with('error', 'No active open time session found for this space.');
        }

        DB::transaction(function () use ($space, $reservation) {
            $endTime = Carbon::now();
            $startTime = Carbon::parse($reservation->start_time);
            $durationInHours = $startTime->diffInHours($endTime);
            if ($durationInHours < 1) {
                $durationInHours = 1; // Minimum 1 hour charge
            }
            $totalCost = $durationInHours * $reservation->hourly_rate;

            $reservation->update([
                'end_time' => $endTime,
                'total_cost' => $totalCost,
                'status' => 'completed',
            ]);

            $space->update([
                'status' => 'available',
                'current_customer_id' => null,
                'occupied_from' => null,
                'occupied_until' => null,
            ]);

            $space->spaceType->increment('available_slots');
        });

        return back()->with('success', 'Open time ended successfully. Total cost calculated.');
    }

    // Create a new space type or increase slots for an existing one (sub spaces)
    public function storeSpaceType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hourly_rate' => 'required|numeric|min:0',
            'default_discount_hours' => 'nullable|integer|min:1',
            'default_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'initial_slots' => 'required|integer|min:1',
        ]);

        // Try to find existing by name (case-insensitive); if exists, add spaces; else create new type
        $spaceType = SpaceType::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->first();
        if (!$spaceType) {
            $spaceType = SpaceType::create([
                'name' => strtoupper($validated['name']),
                'default_price' => $validated['hourly_rate'],
                'hourly_rate' => $validated['hourly_rate'],
                'default_discount_hours' => $validated['default_discount_hours'] ?? null,
                'default_discount_percentage' => $validated['default_discount_percentage'] ?? null,
                'total_slots' => $validated['initial_slots'],
                'available_slots' => $validated['initial_slots'],
                'description' => $validated['description'] ?? null,
            ]);
        } else {
            // Increase declared slot capacity, availability increments by newly created spaces
            $spaceType->increment('total_slots', $validated['initial_slots']);
            $spaceType->increment('available_slots', $validated['initial_slots']);
        }

        // Create concrete spaces under this space type
        $count = $validated['initial_slots'];
        for ($i = 0; $i < $count; $i++) {
            $nextNum = $spaceType->spaces()->count() + 1;
            Space::create([
                'space_type_id' => $spaceType->id,
                'name' => $spaceType->name . ' ' . $nextNum,
                'status' => 'available',
                'hourly_rate' => $spaceType->hourly_rate,
                'discount_hours' => $spaceType->default_discount_hours,
                'discount_percentage' => $spaceType->default_discount_percentage,
            ]);
        }

        return redirect()->back()->with('success', 'Space type updated and spaces created.');
    }

    // Create a single space under a space type with custom properties
    public function storeSpace(Request $request, SpaceType $spaceType)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'hourly_rate' => 'nullable|numeric|min:0',
            'discount_hours' => 'nullable|integer|min:1',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $nextNum = $spaceType->spaces()->count() + 1;
        $space = Space::create([
            'space_type_id' => $spaceType->id,
            'name' => $validated['name'] ?? ($spaceType->name . ' ' . $nextNum),
            'status' => 'available',
            'hourly_rate' => $validated['hourly_rate'] ?? $spaceType->hourly_rate,
            'discount_hours' => $validated['discount_hours'] ?? $spaceType->default_discount_hours,
            'discount_percentage' => $validated['discount_percentage'] ?? $spaceType->default_discount_percentage,
        ]);

        // Update slots counters
        $spaceType->increment('total_slots', 1);
        $spaceType->increment('available_slots', 1);

        return redirect()->back()->with('success', 'New space created: ' . $space->name);
    }

    // Permanently remove a single space (only if available)
    public function destroySpace(Space $space)
    {
        if ($space->status !== 'available') {
            return redirect()->back()->with('error', 'Cannot delete an occupied space. Release it first.');
        }

        $spaceType = $space->spaceType;
        $space->delete();

        // Update counters
        if ($spaceType) {
            $spaceType->decrement('total_slots', 1);
            $spaceType->decrement('available_slots', 1);
        }

        return redirect()->back()->with('success', 'Space removed permanently.');
    }

    // Bulk remove available subspaces from a type (only removes available spaces)
    public function bulkDestroySpaces(Request $request, SpaceType $spaceType)
    {
        $data = $request->validate([
            'count' => 'required|integer|min:1',
        ]);

        $count = (int) $data['count'];
        $spaces = $spaceType->spaces()
            ->where('status', 'available')
            ->orderByDesc('id')
            ->take($count)
            ->get();

        $deleted = 0;
        foreach ($spaces as $s) {
            $s->delete();
            $deleted++;
        }

        if ($deleted > 0) {
            $spaceType->decrement('total_slots', $deleted);
            $spaceType->decrement('available_slots', $deleted);
        }

        if ($deleted === 0) {
            return redirect()->back()->with('error', 'No available spaces to remove.');
        }

        return redirect()->back()->with('success', "Removed {$deleted} available space(s).");
    }

    // Remove an entire space type (only if no occupied spaces remain)
    public function destroySpaceType(SpaceType $spaceType)
    {
        // Prevent deleting when any space is occupied
        $occupiedCount = $spaceType->spaces()->where('status', 'occupied')->count();
        if ($occupiedCount > 0) {
            return redirect()->back()->with('error', 'Cannot delete this space type while there are occupied spaces. Release them first.');
        }

        // Delete all remaining spaces under this type first
        $spaces = $spaceType->spaces()->get();
        foreach ($spaces as $s) {
            $s->delete();
        }

        // Finally, delete the space type
        $spaceType->delete();

        return redirect()->back()->with('success', 'Space type removed successfully.');
    }
}
