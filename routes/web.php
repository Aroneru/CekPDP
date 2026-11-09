<?php

declare(strict_types=1);

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\LocaleController;
use App\Http\Middleware\PreventCaching;
use Illuminate\Support\Facades\Route;

// Pages that can contain policy text are never stored by browsers or proxies.
Route::middleware(PreventCaching::class)->group(function (): void {
    Route::get('/', [AnalysisController::class, 'create'])->name('home');

    Route::post('/analyses', [AnalysisController::class, 'store'])
        ->middleware('throttle:submissions')
        ->name('analyses.store');

    Route::get('/analyses/{analysis}', [AnalysisController::class, 'show'])
        ->whereUlid('analysis')
        ->name('analyses.show');

    Route::delete('/analyses/{analysis}', [AnalysisController::class, 'destroy'])
        ->whereUlid('analysis')
        ->name('analyses.destroy');
});

Route::post('/locale', LocaleController::class)->name('locale.update');
