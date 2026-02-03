<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Public auth routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

/*
|--------------------------------------------------------------------------
| Category Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{slug}', [CategoryController::class, 'show']);
    Route::get('/{slug}/fields', [CategoryController::class, 'show']); // Alias for frontend convenience
});

/*
|--------------------------------------------------------------------------
| Inventory Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('inventory')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::post('/start', [InventoryController::class, 'start']);
    Route::get('/processes', [InventoryController::class, 'processes']);
    Route::get('/{processId}/status', [InventoryController::class, 'status']);
    Route::get('/{id}', [InventoryController::class, 'show']);
    Route::put('/{id}', [InventoryController::class, 'update']);
    Route::post('/{id}/images', [InventoryController::class, 'uploadImage']);
    Route::put('/{id}/images/{image}/primary', [InventoryController::class, 'setPrimaryImage']);
    Route::delete('/{id}/images/{image}', [InventoryController::class, 'deleteImage']);
});
