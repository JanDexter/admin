<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role if provided
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Search functionality
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get user statistics
        $stats = [
            'total_users' => User::count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'staff_users' => User::where('role', 'staff')->count(),
            'customer_users' => User::where('role', 'customer')->count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
        ];

        return Inertia::render('UserManagement/Index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['role', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return Inertia::render('UserManagement/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['customer', 'staff', 'admin'])],
            'is_active' => 'boolean',
        ]);

        // Enforce single admin policy - block creating a second admin
        if ($validated['role'] === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->exists()) {
            return back()
                ->withErrors(['role' => 'Only one admin account is allowed. Demote the existing admin before assigning admin to another user.'])
                ->withInput();
        }

        $validated['password'] = bcrypt($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        User::create($validated);

        return redirect()->route('user-management.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return Inertia::render('UserManagement/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return Inertia::render('UserManagement/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['customer', 'staff', 'admin'])],
            'is_active' => 'boolean',
        ]);

        // Prevent demoting or deactivating the sole admin
        $isChangingFromAdmin = $user->isAdmin() && $validated['role'] !== User::ROLE_ADMIN;
        $isDeactivatingAdmin = $user->isAdmin() && array_key_exists('is_active', $validated) && ! $validated['is_active'];
        if ($isChangingFromAdmin || $isDeactivatingAdmin) {
            return back()
                ->withErrors(['role' => 'The single admin account cannot be demoted or deactivated.'])
                ->withInput();
        }

        // Prevent promoting another user to admin if an admin already exists (and it's not this user)
        if ($validated['role'] === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->where('id', '!=', $user->id)->exists()) {
            return back()
                ->withErrors(['role' => 'Only one admin account is allowed. Demote the existing admin before promoting another user.'])
                ->withInput();
        }

        // Only update password if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $validated['password'] = bcrypt($request->password);
        }

        $user->update($validated);

        return redirect()->route('user-management.index')->with('success', 'User updated successfully.');
    }

    /**
     * Toggle user status (activate/deactivate).
     */
    public function toggleStatus(User $user)
    {
        // Prevent deactivating the sole admin
        if ($user->isAdmin() && $user->is_active) {
            return redirect()->back()->withErrors(['status' => 'The single admin account cannot be deactivated.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "User {$status} successfully.");
    }
}
