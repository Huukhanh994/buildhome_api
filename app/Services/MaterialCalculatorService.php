<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ComponentCalculationInput;
use App\DTOs\ComponentEstimateResult;
use App\Enums\ComponentType;

/**
 * Core material and cost estimation engine.
 *
 * Mirrors the formulas in the Flutter ComponentCalculatorService so both
 * client (local fallback) and server produce identical numbers.
 *
 * To add a new component: add a match arm in calculate() and a private method.
 */
final class MaterialCalculatorService
{
    public function __construct(private readonly PricingService $pricing) {}

    public function calculate(ComponentCalculationInput $input): ComponentEstimateResult
    {
        return match ($input->component) {
            ComponentType::RoofMain,
            ComponentType::RoofSub     => $this->roof($input->area),
            ComponentType::WallFront   => $this->wall($input->area),
            ComponentType::DoorMain    => $this->door($input->area),
            ComponentType::WindowFront => $this->window($input->area),
            ComponentType::Foundation  => $this->foundation($input->area),
            ComponentType::Column      => $this->column($input->area),
            ComponentType::Balcony     => $this->balcony($input->area),
            ComponentType::Garden      => $this->garden($input->area),
        };
    }

    // ── Component formulas ────────────────────────────────────────────────────

    /**
     * Roof: curved tile + cement mortar bed + steel bracing.
     * 22 tiles/m², mortar 0.03 m³/m², steel bracing 4 kg/m².
     */
    private function roof(float $area): ComponentEstimateResult
    {
        $tiles     = round($area * 22);
        $cement    = round($area * 0.03 * 300 / 50);  // bags
        $sand      = round($area * 0.03 * 0.5, 1);    // m³
        $steel     = round($area * 4);                 // kg

        $matCost   = $tiles  * $this->pricing->roofTile()
                   + $cement * $this->pricing->cement()
                   + $sand   * $this->pricing->sand()
                   + $steel  * $this->pricing->steel();

        $laborCost = $area * 120_000 * $this->pricing->regionMultiplier();

        return new ComponentEstimateResult(
            cement:       $cement,
            sand:         $sand,
            steel:        $steel,
            brick:        0,
            concrete:     0,
            roofTile:     $tiles,
            materialCost: $matCost,
            laborCost:    $laborCost,
        );
    }

    /**
     * Wall: brick + mortar + plaster.
     * 60 bricks/m² (200 mm wall), cement 0.25 m³/m², sand 0.06 m³/m².
     */
    private function wall(float $area): ComponentEstimateResult
    {
        $bricks  = round($area * 60);
        $cement  = round($area * 0.25 * 300 / 50);
        $sand    = round($area * 0.06, 1);

        $matCost   = $bricks * $this->pricing->brick()
                   + $cement * $this->pricing->cement()
                   + $sand   * $this->pricing->sand();

        $laborCost = $area * 150_000 * $this->pricing->regionMultiplier();

        return new ComponentEstimateResult(
            cement:       $cement,
            sand:         $sand,
            steel:        0,
            brick:        $bricks,
            concrete:     0,
            roofTile:     0,
            materialCost: $matCost,
            laborCost:    $laborCost,
        );
    }

    /** Door: steel door unit cost 4M VND/m² + frame install. */
    private function door(float $area): ComponentEstimateResult
    {
        $matCost   = $area * $this->pricing->doorPerM2();
        $laborCost = $area * 300_000 * $this->pricing->regionMultiplier();

        return new ComponentEstimateResult(
            cement: 0, sand: 0, steel: 0, brick: 0,
            concrete: 0, roofTile: 0,
            materialCost: $matCost,
            laborCost:    $laborCost,
        );
    }

    /** Window: aluminum frame + glass 2.5M VND/m². */
    private function window(float $area): ComponentEstimateResult
    {
        $matCost   = $area * $this->pricing->windowPerM2();
        $laborCost = $area * 200_000 * $this->pricing->regionMultiplier();

        return new ComponentEstimateResult(
            cement: 0, sand: 0, steel: 0, brick: 0,
            concrete: 0, roofTile: 0,
            materialCost: $matCost,
            laborCost:    $laborCost,
        );
    }

    /**
     * Foundation: reinforced concrete.
     * 0.5 m³ concrete/m² section, 90 kg steel/m² equiv.
     */
    private function foundation(float $area): ComponentEstimateResult
    {
        $concrete = round($area * 0.5, 1);
        $steel    = round($area * 90);
        $cement   = round($concrete * 300 / 50);
        $sand     = round($concrete * 0.5, 1);

        $matCost   = $concrete * $this->pricing->concrete()
                   + $steel    * $this->pricing->steel()
                   + $cement   * $this->pricing->cement()
                   + $sand     * $this->pricing->sand();

        $laborCost = $area * 250_000 * $this->pricing->regionMultiplier();

        return new ComponentEstimateResult(
            cement:       $cement,
            sand:         $sand,
            steel:        $steel,
            brick:        0,
            concrete:     $concrete,
            roofTile:     0,
            materialCost: $matCost,
            laborCost:    $laborCost,
        );
    }

    /** Column: 300×300 mm reinforced concrete column. */
    private function column(float $area): ComponentEstimateResult
    {
        $concrete = round($area * 0.09, 2);
        $steel    = round($area * 120);
        $cement   = round($concrete * 350 / 50);

        $matCost   = $concrete * $this->pricing->concrete()
                   + $steel    * $this->pricing->steel()
                   + $cement   * $this->pricing->cement();

        $laborCost = $area * 300_000 * $this->pricing->regionMultiplier();

        return new ComponentEstimateResult(
            cement:       $cement,
            sand:         0,
            steel:        $steel,
            brick:        0,
            concrete:     $concrete,
            roofTile:     0,
            materialCost: $matCost,
            laborCost:    $laborCost,
        );
    }

    /** Balcony: RC slab + railing. */
    private function balcony(float $area): ComponentEstimateResult
    {
        $concrete = round($area * 0.12, 1);
        $steel    = round($area * 80);

        $matCost   = $concrete * $this->pricing->concrete()
                   + $steel    * $this->pricing->steel()
                   + $area     * 500_000;

        $laborCost = $area * 200_000 * $this->pricing->regionMultiplier();

        return new ComponentEstimateResult(
            cement: 0, sand: 0,
            steel:    $steel,
            brick:    0,
            concrete: $concrete,
            roofTile: 0,
            materialCost: $matCost,
            laborCost:    $laborCost,
        );
    }

    /** Garden: landscaping flat rate. */
    private function garden(float $area): ComponentEstimateResult
    {
        $matCost   = $area * $this->pricing->landscapePerM2();
        $laborCost = $area * 80_000 * $this->pricing->regionMultiplier();

        return new ComponentEstimateResult(
            cement: 0, sand: 0, steel: 0, brick: 0,
            concrete: 0, roofTile: 0,
            materialCost: $matCost,
            laborCost:    $laborCost,
        );
    }
}
