<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Admin;
use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class UserPermissionController extends Controller
{
    /**
     * Show permission management for a user
     */
    public function edit(User $user)
    {
        // Load relationships
        $user->load(['customer', 'staff', 'admin']);

        // Get all available permissions
        $allPermissions = Permission::getAllPermissions();

        // Get user's current permissions
        $currentPermissions = $user->getAllPermissions();

        // Get preset templates
        $presets = [
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Full system access (except deletion)',
                'permissions' => Permission::getPresetPermissions('admin'),
            ],
            'staff' => [
                'name' => 'Staff Member',
                'description' => 'Operational access for front desk',
                'permissions' => Permission::getPresetPermissions('staff'),
            ],
            'customer' => [
                'name' => 'Customer',
                'description' => 'Basic customer access',
                'permissions' => Permission::getPresetPermissions('customer'),
            ],
        ];

        return Inertia::render('UserManagement/Permissions', [
            'user' => $user,
            'allPermissions' => $allPermissions,
            'currentPermissions' => $currentPermissions,
            'presets' => $presets,
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * Update user roles and permissions
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'in:admin,staff,customer',
            'admin_level' => 'nullable|in:super_admin,admin,moderator',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        DB::beginTransaction();
        try {
            // Handle role changes
            $newRoles = $validated['roles'];
            $currentRoles = $user->getRoles();

            // Add/remove customer role
            if (in_array('customer', $newRoles) && !in_array('customer', $currentRoles)) {
                Customer::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                    'status' => 'active',
                ]);
            } elseif (!in_array('customer', $newRoles) && in_array('customer', $currentRoles)) {
                $user->customer()->delete();
            }

            // Add/remove staff role
            if (in_array('staff', $newRoles) && !in_array('staff', $currentRoles)) {
                Staff::create([
                    'user_id' => $user->id,
                    'employee_id' => 'EMP-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'department' => 'General',
                    'hired_date' => now(),
                ]);
            } elseif (!in_array('staff', $newRoles) && in_array('staff', $currentRoles)) {
                $user->staff()->delete();
            }

            // Add/remove admin role
            if (in_array('admin', $newRoles) && !in_array('admin', $currentRoles)) {
                Admin::create([
                    'user_id' => $user->id,
                    'permission_level' => $validated['admin_level'] ?? 'admin',
                    'permissions' => $validated['permissions'] ?? Permission::getPresetPermissions('admin'),
                ]);
            } elseif (!in_array('admin', $newRoles) && in_array('admin', $currentRoles)) {
                $user->admin()->delete();
            } elseif (in_array('admin', $newRoles) && $user->admin) {
                // Update existing admin
                $user->admin->update([
                    'permission_level' => $validated['admin_level'] ?? $user->admin->permission_level,
                    'permissions' => $validated['permissions'] ?? $user->admin->permissions,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('user-management.show', $user)
                ->with('success', 'User roles and permissions updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update permissions: ' . $e->getMessage()]);
        }
    }

    /**
     * Apply preset permissions
     */
    public function applyPreset(Request $request, User $user)
    {
        $validated = $request->validate([
            'preset' => 'required|in:admin,staff,customer',
        ]);

        $presetType = $validated['preset'];

        DB::beginTransaction();
        try {
            // Remove all existing roles
            $user->customer()->delete();
            $user->staff()->delete();
            $user->admin()->delete();

            // Apply preset based on type
            switch ($presetType) {
                case 'admin':
                    Admin::create([
                        'user_id' => $user->id,
                        'permission_level' => 'admin',
                        'permissions' => Permission::getPresetPermissions('admin'),
                    ]);
                    // Don't add customer role by default for admin
                    break;

                case 'staff':
                    Staff::create([
                        'user_id' => $user->id,
                        'employee_id' => 'EMP-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                        'department' => 'General',
                        'hired_date' => now(),
                    ]);
                    break;

                case 'customer':
                    Customer::create([
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone ?? '',
                        'status' => 'active',
                    ]);
                    break;
            }

            DB::commit();

            return redirect()
                ->route('user-management.show', $user)
                ->with('success', ucfirst($presetType) . ' preset applied successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to apply preset: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle specific permission for admin user
     */
    public function togglePermission(Request $request, User $user)
    {
        if (!$user->admin) {
            return back()->withErrors(['error' => 'User must be an admin to manage permissions.']);
        }

        $validated = $request->validate([
            'permission' => 'required|string',
        ]);

        $permission = $validated['permission'];

        if ($user->admin->hasPermission($permission)) {
            $user->admin->revokePermission($permission);
            $message = 'Permission revoked.';
        } else {
            $user->admin->grantPermission($permission);
            $message = 'Permission granted.';
        }

        return back()->with('success', $message);
    }
}
