<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ComponentCalculationInput;
use App\Enums\ComponentType;
use App\Enums\HouseType;
use App\Enums\Region;
use App\Http\Controllers\Controller;
use App\Http\Requests\ComponentCalculationRequest;
use App\Http\Resources\ComponentEstimateResource;
use App\Services\MaterialCalculatorService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ComponentCalculationController extends Controller
{
    /** Cache TTL: 6 hours — prices don't change intraday. */
    private const CACHE_TTL_SECONDS = 21_600;

    /**
     * POST /api/v1/component/calculate
     *
     * Calculates material quantities and costs for a single house component.
     * Results are cached by (component, area, house_type, floors, location).
     */
    public function calculate(ComponentCalculationRequest $request): JsonResponse
    {
        $input = $this->buildInput($request);

        $result = Cache::remember(
            $input->cacheKey(),
            self::CACHE_TTL_SECONDS,
            function () use ($input) {
                $pricing    = new PricingService($input->region);
                $calculator = new MaterialCalculatorService($pricing);

                return $calculator->calculate($input);
            },
        );

        return (new ComponentEstimateResource($result, $input))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * POST /api/v1/component/calculate-multiple
     *
     * Calculates and aggregates results for multiple components in one request.
     * Intended for Phase 2 multi-select feature.
     */
    public function calculateMultiple(ComponentCalculationRequest $request): JsonResponse
    {
        // Reuse single-component logic for each item; aggregate totals
        $components = $request->input('components', []);

        $results = collect($components)->map(function (array $item) use ($request) {
            $singleRequest = $request->merge($item);
            $input  = $this->buildInputFromArray($item, $request->input('location', 'default'));
            $pricing    = new PricingService($input->region);
            $calculator = new MaterialCalculatorService($pricing);

            return [
                'input'  => $input,
                'result' => $calculator->calculate($input),
            ];
        });

        $totalMaterialCost = $results->sum(fn ($r) => $r['result']->materialCost);
        $totalLaborCost    = $results->sum(fn ($r) => $r['result']->laborCost);

        return response()->json([
            'components' => $results->map(fn ($r) =>
                (new ComponentEstimateResource($r['result'], $r['input']))->toArray($request)
            )->values(),
            'aggregate' => [
                'material_cost' => round($totalMaterialCost),
                'labor_cost'    => round($totalLaborCost),
                'total'         => round($totalMaterialCost + $totalLaborCost),
                'unit'          => 'VND',
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildInput(ComponentCalculationRequest $request): ComponentCalculationInput
    {
        return $this->buildInputFromArray(
            $request->validated(),
            $request->input('location', 'default'),
        );
    }

    private function buildInputFromArray(array $data, string $defaultLocation): ComponentCalculationInput
    {
        return new ComponentCalculationInput(
            component: ComponentType::from($data['component']),
            area:      (float) $data['area'],
            houseType: HouseType::from($data['house_type']),
            floors:    (int) $data['floors'],
            region:    Region::tryFrom($data['location'] ?? $defaultLocation) ?? Region::Default_,
        );
    }
}
