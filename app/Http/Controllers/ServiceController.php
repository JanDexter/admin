<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = Service::with(['customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Services/Index', [
            'services' => $services
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Services/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'price_per_hour' => 'nullable|numeric|min:0',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'amenities' => 'nullable|array',
            'availability_hours' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'active';

        Service::create($validated);

        return redirect()->route('services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        $service->load(['customer', 'user']);

        return Inertia::render('Services/Show', [
            'service' => $service
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        $customers = Customer::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Services/Edit', [
            'service' => $service,
            'customers' => $customers
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'price_per_hour' => 'nullable|numeric|min:0',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'amenities' => 'nullable|array',
            'availability_hours' => 'nullable|array',
            'status' => 'required|in:active,reserved,closed',
            'customer_id' => 'nullable|exists:customers,id',
            'reserved_from' => 'nullable|date',
            'reserved_until' => 'nullable|date|after:reserved_from',
            'notes' => 'nullable|string'
        ]);

        // If status is being changed to reserved, require customer and dates
        if ($validated['status'] === 'reserved') {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'reserved_from' => 'required|date',
                'reserved_until' => 'required|date|after:reserved_from'
            ]);
        }

        // If status is not reserved, clear reservation data
        if ($validated['status'] !== 'reserved') {
            $validated['customer_id'] = null;
            $validated['reserved_from'] = null;
            $validated['reserved_until'] = null;
        }

        $service->update($validated);

        return redirect()->route('services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Close/Complete the transaction (soft delete equivalent)
     */
    public function close(Service $service)
    {
        $service->update([
            'status' => 'closed',
            'customer_id' => null,
            'reserved_from' => null,
            'reserved_until' => null
        ]);

        return redirect()->route('services.index')
            ->with('success', 'Service transaction closed successfully.');
    }

    /**
     * Remove the specified resource from storage.
     * Only for admin purposes - not part of normal workflow
     */
    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('services.index')
            ->with('success', 'Service deleted successfully.');
    }
}
