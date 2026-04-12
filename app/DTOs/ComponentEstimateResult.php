<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ComponentEstimateResult
{
    public function __construct(
        public float $cement,      // bags
        public float $sand,        // m³
        public float $steel,       // kg
        public float $brick,       // pieces
        public float $concrete,    // m³
        public float $roofTile,    // pieces
        public float $materialCost, // VND
        public float $laborCost,    // VND
    ) {}

    public function totalCost(): float
    {
        return $this->materialCost + $this->laborCost;
    }
}
