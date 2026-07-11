<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TrackingController;

// Public routes
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Welcome to TepTep API',
        'version' => '1.0.0',
        'service' => 'GIE Transport SaaS'
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/payments/callback', [TicketController::class, 'paymentsCallback']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Lines & Trips
    Route::get('/lines', [LineController::class, 'index']);
    Route::get('/lines/{id}', [LineController::class, 'show']);
    Route::get('/trips', [LineController::class, 'getTrips']);
    Route::post('/admin/lines', [LineController::class, 'store']);

    // Bookings / Tickets
    Route::post('/tickets/book', [TicketController::class, 'book']);
    Route::post('/tickets/book-payment', [TicketController::class, 'bookWithPayment']);
    Route::get('/tickets/my', [TicketController::class, 'myTickets']);
    Route::post('/tickets/scan', [TicketController::class, 'scan']);
    Route::get('/payments/status/{externalTransactionId}', [TicketController::class, 'checkPaymentStatus']);

    // Real-time GPS Tracking
    Route::post('/tracking/update', [TrackingController::class, 'updatePosition']);
    Route::get('/tracking/positions', [TrackingController::class, 'getPositions']);
});
