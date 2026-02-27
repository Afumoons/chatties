<?php

use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ChatController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Chat UI
    Route::get('chat', [ChatController::class, 'index'])->name('chat');

    // Simple message endpoints (MVP)
    Route::get('messages/{user}', [MessageController::class, 'index'])->name('messages.index');
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');

    // Message receipts
    Route::patch('messages/{message}/delivered', [MessageController::class, 'markDelivered'])->name('messages.delivered');
    Route::patch('messages/{message}/read', [MessageController::class, 'markRead'])->name('messages.read');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
