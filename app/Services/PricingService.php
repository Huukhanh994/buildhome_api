<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Region;

/**
 * Centralises all unit prices used by MaterialCalculatorService.
 * Each price is in VND. Regional overrides are applied on top of defaults.
 */
final class PricingService
{
    // ── Global defaults (VND / unit) ─────────────────────────────────────────

    private const CEMENT_BAG   = 90_000;   // 50 kg bag
    private const SAND_M3      = 350_000;
    private const STEEL_KG     = 20_000;
    private const BRICK_PIECE  = 2_000;
    private const ROOF_TILE    = 8_000;
    private const CONCRETE_M3  = 1_500_000;
    private const DOOR_PER_M2  = 4_000_000;
    private const WINDOW_PER_M2 = 2_500_000;
    private const LANDSCAPE_M2 = 200_000;

    public function __construct(private readonly Region $region) {}

    public function cement(): int
    {
        return $this->region->cementPriceOverride() ?? self::CEMENT_BAG;
    }

    public function sand(): float
    {
        return self::SAND_M3 * $this->region->multiplier();
    }

    public function steel(): float
    {
        return self::STEEL_KG * $this->region->multiplier();
    }

    public function brick(): float
    {
        return self::BRICK_PIECE * $this->region->multiplier();
    }

    public function roofTile(): float
    {
        return self::ROOF_TILE * $this->region->multiplier();
    }

    public function concrete(): float
    {
        return self::CONCRETE_M3 * $this->region->multiplier();
    }

    public function doorPerM2(): float
    {
        return self::DOOR_PER_M2 * $this->region->multiplier();
    }

    public function windowPerM2(): float
    {
        return self::WINDOW_PER_M2 * $this->region->multiplier();
    }

    public function landscapePerM2(): float
    {
        return self::LANDSCAPE_M2 * $this->region->multiplier();
    }

    /** Exposed so MaterialCalculatorService can apply it to labour rates. */
    public function regionMultiplier(): float
    {
        return $this->region->multiplier();
    }
}
