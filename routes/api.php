<?php

use App\Http\Controllers\Api\InventoryController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PermissionController;

Route::middleware(['auth:sanctum', \App\Http\Middleware\TrackApiUsage::class])->group(function () {
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'externalIndex']);
        Route::get('/{id}', [InventoryController::class, 'externalShow']);
        Route::post('/{id}/images/external', [InventoryController::class, 'addExternalImage']);
    });

    Route::post('leads', [LeadController::class, 'store']);
});
