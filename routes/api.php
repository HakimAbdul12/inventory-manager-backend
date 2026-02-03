<?php

use App\Http\Controllers\Api\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->middleware(['auth:sanctum', \App\Http\Middleware\TrackApiUsage::class])->group(function () {
    Route::get('/', [InventoryController::class, 'externalIndex']);
    Route::get('/{id}', [InventoryController::class, 'externalShow']);
});
