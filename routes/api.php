<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShipmentController;

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

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes requiring authentication
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
    
    // Shipment routes
    Route::prefix('shipments')->group(function () {
        Route::get('/', [ShipmentController::class, 'index']);
        Route::post('/', [ShipmentController::class, 'store']);
        Route::get('/{shipment}', [ShipmentController::class, 'show']);
        Route::post('/quote', [ShipmentController::class, 'getPriceQuote']);
        Route::post('/{shipment}/accept', [ShipmentController::class, 'acceptShipment']);
        Route::put('/{shipment}/status', [ShipmentController::class, 'updateStatus']);
        Route::delete('/{shipment}', [ShipmentController::class, 'cancel']);
    });

    // User endpoint (backward compatibility)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Vehicle types and cities (public access for UI purposes)
Route::get('vehicle-types', function () {
    return response()->json([
        'success' => true,
        'data' => \App\VehicleType::all()
    ]);
});

Route::get('cities', function () {
    return response()->json([
        'success' => true,
        'data' => \App\City::all()
    ]);
});