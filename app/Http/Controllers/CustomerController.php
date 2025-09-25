<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SpaceType;
use App\Models\Space;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    private function normalizePhone(?string $raw): ?string
    {
        if (!$raw) return null;
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === null || $digits === '') return null;
        // Convert +63xxxxxxxxxx or 63xxxxxxxxxx to 0xxxxxxxxxx
        if (preg_match('/^63(9\d{9})$/', $digits, $m)) {
            return '0' . $m[1];
        }
        // Convert 9xxxxxxxxx to 09xxxxxxxxx
        if (preg_match('/^9\d{9}$/', $digits)) {
            return '0' . $digits;
        }
        // Keep as-is (likely already starts with 0)
        return $digits;
    }
    public function index()
    {
        $customers = Customer::with(['user', 'spaceType'])
            // Removed tasks count since Task Tracker deprecated
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
        // Normalize phone to a consistent 09XXXXXXXXX format when possible
        if ($request->has('phone')) {
            $request->merge(['phone' => $this->normalizePhone($request->input('phone'))]);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => ['nullable','regex:/^09\d{9}$/'],
            'address' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
            'space_type_id' => 'nullable|exists:space_types,id',
        ], [
            'phone.regex' => 'Phone must start with 09 and be 11 digits.',
        ]);

    $validated['user_id'] = Auth::id();
        $validated['status'] = 'active';

        $validated['company_name'] = strip_tags($validated['company_name'] ?? '');
        $validated['contact_person'] = strip_tags($validated['contact_person'] ?? '');
        $validated['address'] = isset($validated['address']) ? strip_tags($validated['address']) : null;
        $validated['notes'] = isset($validated['notes']) ? strip_tags($validated['notes']) : null;

        $customer = Customer::create($validated);

        if ($request->boolean('inline')) {
            return back()->with('success', 'Customer created successfully.')
                         ->with('customer', $customer);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Customer created successfully.')
            ->with('customer', $customer);
    }

    public function show(Customer $customer)
    {
        $customer->load(['tasks' => function ($query) {
            $query->with('user')->orderBy('created_at', 'desc');
        }]);

    $user = Auth::user();
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
        if ($request->has('phone')) {
            $request->merge(['phone' => $this->normalizePhone($request->input('phone'))]);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,' . $customer->id,
            'phone' => ['nullable','regex:/^09\d{9}$/'],
            'address' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
        ], [
            'phone.regex' => 'Phone must start with 09 and be 11 digits.',
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
