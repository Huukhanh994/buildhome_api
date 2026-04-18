<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ComponentCalculationController;
use App\Http\Controllers\Api\V1\HouseModelController;
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

    // ── Auth (phone + OTP) ─────────────────────────────────────────────────

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('send-otp', [AuthController::class, 'sendOtp'])->name('send-otp');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });

    // ── House 3D models ────────────────────────────────────────────────────

    Route::prefix('house-models')->name('house-models.')->group(function () {
        // Public: list + show + file proxy (CORS-safe)
        Route::get('/',                    [HouseModelController::class, 'index'])->name('index');
        Route::get('{houseModel}',         [HouseModelController::class, 'show'])->name('show');
        Route::get('{houseModel}/file',    [HouseModelController::class, 'serveGlb'])->name('file');

        // Protected: upload + delete (require auth)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/',              [HouseModelController::class, 'store'])->name('store');
            Route::delete('{houseModel}', [HouseModelController::class, 'destroy'])->name('destroy');
        });
    });

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
