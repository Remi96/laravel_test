<?php

use App\Http\Controllers\ShopController;

Route::middleware('auth:sanctum')->post('/shops', [ShopController::class, 'store']);