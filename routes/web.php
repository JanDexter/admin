<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SpaceManagementController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing', [
        'canLogin' => Route::has('login'),
        'canRegister' => false,
    ]);
});

// PWA Routes
Route::get('/manifest.json', function () {
    return response()->file(public_path('manifest.json'), [
        'Content-Type' => 'application/json'
    ]);
});

Route::get('/sw.js', function () {
    return response()->file(public_path('sw.js'), [
        'Content-Type' => 'application/javascript'
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Explicitly catch any attempt to access /register and redirect to login
Route::any('/register', function () {
    return redirect()->route('login')->with('status', 'Registration is disabled. Please ask the admin to create your account.');
});

// Comment out email verification middleware temporarily
Route::middleware(['auth'])->group(function () { // Removed 'verified' middleware
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Customer management routes 
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    
    // Service management routes (Co-workspace reservations)
    Route::resource('services', ServiceController::class);
    Route::patch('/services/{service}/close', [ServiceController::class, 'close'])->name('services.close');
    
    // User management routes (Admin only)
    Route::middleware('can:manage-users')->group(function () {
        Route::get('/user-management', [UserManagementController::class, 'index'])->name('user-management.index');
        Route::get('/user-management/create', [UserManagementController::class, 'create'])->name('user-management.create');
        Route::post('/user-management', [UserManagementController::class, 'store'])->name('user-management.store');
        Route::get('/user-management/{user}', [UserManagementController::class, 'show'])->name('user-management.show');
        Route::get('/user-management/{user}/edit', [UserManagementController::class, 'edit'])->name('user-management.edit');
        Route::put('/user-management/{user}', [UserManagementController::class, 'update'])->name('user-management.update');
        Route::patch('/user-management/{user}', [UserManagementController::class, 'update'])->name('user-management.update');
        Route::patch('/user-management/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('user-management.toggle-status');
    });
    
    // Space management routes (Admin only)
    Route::middleware('can:admin-access')->group(function () {
        Route::get('/space-management', [SpaceManagementController::class, 'index'])->name('space-management.index');
        Route::patch('/space-management/space-types/{spaceType}/pricing', [SpaceManagementController::class, 'updatePricing'])->name('space-management.update-pricing');
        Route::patch('/space-management/spaces/{space}/assign', [SpaceManagementController::class, 'assignSpace'])->name('space-management.assign-space');
        Route::patch('/space-management/spaces/{space}/release', [SpaceManagementController::class, 'releaseSpace'])->name('space-management.release-space');
    });
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
