<?php

use App\Http\Controllers\Api\ProductApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:api', 'auth:sanctum', 'abilities:products:read'])->group(function () {
    Route::get('v1/products', [ProductApiController::class, 'index']);
});
