<?php

use Illuminate\Support\Facades\Route;
use Oliweb\StatamicAnalytics\Http\Controllers\ConsentController;
use Oliweb\StatamicAnalytics\Http\Controllers\TrackController;

Route::post('/statamic-analytics/consent', [ConsentController::class, 'store'])
    ->middleware(['web', 'throttle:10,1']);

// Beacon JS tracker — GET sans CSRF (standard analytics, lecture seule côté navigateur).
// 'web' active StartSession pour que TrackController puisse lire la session de consentement.
// VerifyCsrfToken (inclus dans web) est exempt sur GET : aucun risque CSRF.
Route::get('/statamic-analytics/track', [TrackController::class, 'track'])
    ->middleware(['web', 'throttle:120,1'])
    ->name('statamic-analytics.track');