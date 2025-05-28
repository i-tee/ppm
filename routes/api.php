<?php

use App\Http\Controllers\Api\PartnerController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [PartnerController::class, 'me']);
});