<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SpaceType;
use App\Models\Space;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('user')
            ->withCount('tasks')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function create()
    {
        $spaceTypes = SpaceType::with(['spaces' => function($query) {
            $query->where('status', 'available');
        }])->get();

        return Inertia::render('Customers/Create', [
            'spaceTypes' => $spaceTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'service_type' => 'nullable|string|in:CONFERENCE ROOM,SHARED SPACE,EXCLUSIVE SPACE,PRIVATE SPACE,DRAFTING TABLE',
            'service_price' => 'nullable|numeric|min:0',
            'service_start_time' => 'nullable|date',
            'service_end_time' => 'nullable|date|after:service_start_time',
            'amount_paid' => 'nullable|numeric|min:0',
            'space_type_id' => 'nullable|exists:space_types,id',
        ]);

        $validated['company_name'] = strip_tags($validated['company_name'] ?? '');
        $validated['contact_person'] = strip_tags($validated['contact_person'] ?? '');
        $validated['address'] = isset($validated['address']) ? strip_tags($validated['address']) : null;
        $validated['notes'] = isset($validated['notes']) ? strip_tags($validated['notes']) : null;

        // Set service price based on space type if selected
        if (!empty($validated['space_type_id'])) {
            $spaceType = SpaceType::find($validated['space_type_id']);
            if ($spaceType) {
                $validated['service_price'] = $spaceType->default_price;
                $validated['service_type'] = $spaceType->name;
            }
        }

        $customer = Customer::create($validated);

        return redirect()->route('dashboard')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['tasks' => function ($query) {
            $query->with('user')->orderBy('created_at', 'desc');
        }]);

        $user = auth()->user();
        if (! $user->isAdmin() && $customer->user_id !== $user->id) {
            abort(403);
        }

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'service_type' => 'nullable|string|in:CONFERENCE ROOM,SHARED SPACE,EXCLUSIVE SPACE,PRIVATE SPACE,DRAFTING TABLE',
            'service_price' => 'nullable|numeric|min:0',
            'service_start_time' => 'nullable|date',
            'service_end_time' => 'nullable|date|after:service_start_time',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $validated['company_name'] = strip_tags($validated['company_name'] ?? '');
        $validated['contact_person'] = strip_tags($validated['contact_person'] ?? '');
        $validated['address'] = isset($validated['address']) ? strip_tags($validated['address']) : null;
        $validated['notes'] = isset($validated['notes']) ? strip_tags($validated['notes']) : null;

        $customer->update($validated);

        return redirect()->route('dashboard')
            ->with('success', 'Customer updated successfully.');
    }
}
