<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// Route::get('/health', function () {
//     return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
// });


// Route::prefix('auth')->group(function () {
//     Route::post('/register', [AuthController::class, 'register']);
//     Route::post('/login', [AuthController::class, 'login']);
//     Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
//     Route::post('/reset-password', [AuthController::class, 'resetPassword']);
//     Route::middleware('auth:sanctum')->group(function () {
//         Route::post('/logout', [AuthController::class, 'logout']);
//         Route::get('/user', [AuthController::class, 'user']);
//     });
// });


// Route::prefix('categories')->group(function () {
//     Route::get('/', [CategoryController::class, 'index']);
//     Route::get('/{slug}', [CategoryController::class, 'show']);
//     Route::get('/{slug}/fields', [CategoryController::class, 'show']);
// });


// Route::prefix('inventory')->middleware('auth:sanctum')->group(function () {
//     Route::get('/', [InventoryController::class, 'index']);
//     Route::post('/start', [InventoryController::class, 'start']);
//     Route::get('/processes', [InventoryController::class, 'processes']);
//     Route::get('/{processId}/status', [InventoryController::class, 'status']);
//     Route::get('/{id}', [InventoryController::class, 'show']);
// });
