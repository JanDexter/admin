<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SetupController extends Controller
{
    public function showSetupForm()
    {
        // Only show if no admin exists
        if (User::where('role', 'admin')->count() > 0) {
            return redirect('/login');
        }
        
        return Inertia::render('Setup/AdminSetup');
    }

    public function storeAdmin(Request $request)
    {
        // Double-check no admin exists
        if (User::where('role', 'admin')->count() > 0) {
            return redirect('/login')->with('error', 'Admin already exists');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        return redirect('/login')->with('success', 'Admin account created successfully! You can now log in.');
    }
}
