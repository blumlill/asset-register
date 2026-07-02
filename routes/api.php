<?php

declare(strict_types=1);

use App\Http\Controllers\AssetController;
use App\Http\Controllers\ContractController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::apiResource('assets', AssetController::class)->except(['create', 'edit']);
    Route::apiResource('contracts', ContractController::class)->except(['create', 'edit']);
    Route::post('contracts/{id}/assets', [ContractController::class, 'assignAsset']);
    Route::delete('contracts/{id}/assets/{assetId}', [ContractController::class, 'removeAsset']);
});
