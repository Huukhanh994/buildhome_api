<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ComponentCalculationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| BuildHome API Routes — v1
|--------------------------------------------------------------------------
|
| All routes here are automatically prefixed with /api by the
| bootstrap/app.php API middleware group.
|
*/

Route::prefix('v1')->name('v1.')->group(function () {

    // ── Component material calculation ─────────────────────────────────────

    Route::prefix('component')->name('component.')->group(function () {

        /**
         * Single component calculation
         *
         * POST /api/v1/component/calculate
         * Body: { component, area, house_type, floors, location? }
         */
        Route::post('calculate', [ComponentCalculationController::class, 'calculate'])
            ->name('calculate');

        /**
         * Multi-component aggregated calculation (Phase 2)
         *
         * POST /api/v1/component/calculate-multiple
         * Body: { components: [{component, area, house_type, floors}], location? }
         */
        Route::post('calculate-multiple', [ComponentCalculationController::class, 'calculateMultiple'])
            ->name('calculate-multiple');
    });

    // ── Health check ───────────────────────────────────────────────────────

    Route::get('health', fn () => response()->json([
        'status'  => 'ok',
        'version' => 'v1.0',
        'service' => 'buildhome-api',
    ]))->name('health');
});
