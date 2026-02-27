<?php

use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use App\Http\Controllers\MessageController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Simple message endpoints (MVP)
    Route::get('messages/{user}', [MessageController::class, 'index'])->name('messages.index');
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
