<?php

use Illuminate\Support\Facades\Route;
use Oli217\EnhancedAnalytics\Http\Controllers\ConsentController;

Route::post('/enhanced-analytics/consent', [ConsentController::class, 'store'])
    ->middleware(['web']); 