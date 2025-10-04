<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/api/stats', [LandingController::class, 'getStats'])->name('api.stats');

// WhatsApp webhook principal
Route::post('/webhook/whatsapp', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle'])
    ->name('whatsapp.webhook.main');

// WhatsApp webhook pour status callbacks
Route::post('/webhook/whatsapp/status', function (Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('WhatsApp Status Callback', $request->all());
    return response('OK', 200);
});

// Routes Horizon protégées par middleware auth et permission
Route::middleware(['auth', 'can:access horizon'])->group(function () {
    Route::get('/horizon', function () {
        return redirect('/horizon/dashboard');
    });
});
