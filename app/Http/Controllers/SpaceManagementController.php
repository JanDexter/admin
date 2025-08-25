<?php

namespace App\Http\Controllers;

use App\Models\SpaceType;
use App\Models\Space;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpaceManagementController extends Controller
{
    public function index()
    {
        $spaceTypes = SpaceType::with(['spaces' => function($query) {
            $query->with('currentCustomer');
        }])->get();

        $customers = Customer::where('status', 'active')
            ->select('id', 'company_name', 'contact_person')
            ->orderBy('company_name')
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
            'default_discount_hours' => 'nullable|integer|min:1',
            'default_discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

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
        ]);

        if (!$space->isAvailable()) {
            return redirect()->back()->with('error', 'Space is not available.');
        }

        $space->occupy(
            $validated['customer_id'],
            now(),
            $validated['occupied_until'] ?? null
        );

        return redirect()->back()->with('success', "Space {$space->name} has been assigned.");
    }
}
