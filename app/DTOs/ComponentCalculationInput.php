<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ComponentType;
use App\Enums\HouseType;
use App\Enums\Region;

final readonly class ComponentCalculationInput
{
    public function __construct(
        public ComponentType $component,
        public float         $area,
        public HouseType     $houseType,
        public int           $floors,
        public Region        $region,
    ) {}

    /** Stable cache key for this exact calculation configuration */
    public function cacheKey(): string
    {
        return implode(':', [
            'buildhome',
            'calc',
            'v1',
            $this->component->value,
            number_format($this->area, 2, '.', ''),
            $this->houseType->value,
            $this->floors,
            $this->region->value,
        ]);
    }
}
