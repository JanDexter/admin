<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

$adminLoginPath = trim(config('app.admin_login_path', 'coz-control-access'), '/');
$adminAreaPrefix = trim(config('app.admin_area_prefix', 'coz-control'), '/');

Route::middleware('guest')->group(function () use ($adminLoginPath) {
    // Registration is disabled - only admin can create accounts via user management
    // Route::get('register', [RegisteredUserController::class, 'create'])
    //     ->name('register');
    // Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get($adminLoginPath, [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post($adminLoginPath, [AuthenticatedSessionController::class, 'store']);

    Route::get($adminLoginPath.'/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post($adminLoginPath.'/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get($adminLoginPath.'/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post($adminLoginPath.'/reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () use ($adminAreaPrefix) {
    Route::get($adminAreaPrefix.'/verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get($adminAreaPrefix.'/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post($adminAreaPrefix.'/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get($adminAreaPrefix.'/confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post($adminAreaPrefix.'/confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put($adminAreaPrefix.'/password', [PasswordController::class, 'update'])->name('password.update');

    Route::post($adminAreaPrefix.'/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
