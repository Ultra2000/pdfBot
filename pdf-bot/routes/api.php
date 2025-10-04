<?php

use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\MetaWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Twilio WhatsApp webhook routes (accessible sans CSRF)
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->name('whatsapp.webhook.main');

Route::prefix('whatsapp')->group(function () {
    Route::post('/webhook', [WhatsAppWebhookController::class, 'handle'])
        ->name('whatsapp.webhook');
});

// Meta WhatsApp webhook routes
Route::prefix('meta')->group(function () {
    Route::get('/webhook', [MetaWebhookController::class, 'verify'])
        ->name('meta.webhook.verify');
    
    Route::post('/webhook', [MetaWebhookController::class, 'handle'])
        ->name('meta.webhook.handle');
});
