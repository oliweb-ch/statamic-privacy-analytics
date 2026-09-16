<?php

use Illuminate\Support\Facades\Route;
use Oliweb\StatamicAnalytics\Http\Controllers\ConsentController;
use Oliweb\StatamicAnalytics\Http\Controllers\TrackController;

Route::post('/statamic-analytics/consent', [ConsentController::class, 'store'])
    ->middleware(['web', 'throttle:10,1']);

// Beacon analytics — GET sans CSRF.
// 'web' active StartSession : lorsque consent.enabled est actif, TrackController
// lit session('analytics_consent') pour refuser les beacons non consentis côté serveur.
// La session n'est ni créée ni modifiée si le consentement est désactivé.
// VerifyCsrfToken (inclus dans web) est exempt sur GET : aucun risque CSRF.
Route::get('/statamic-analytics/track', [TrackController::class, 'track'])
    ->middleware(['web', 'throttle:120,1'])
    ->name('statamic-analytics.track');