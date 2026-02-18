<?php

use App\Http\Controllers\Api\InventoryController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\TransferController;

Route::middleware(['auth:sanctum', \App\Http\Middleware\TrackApiUsage::class])->group(function () {
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'externalIndex']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::post('/{id}/images/external', [InventoryController::class, 'addExternalImage']);
    });
});
