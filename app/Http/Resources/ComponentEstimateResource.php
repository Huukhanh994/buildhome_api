<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\ComponentCalculationInput;
use App\DTOs\ComponentEstimateResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a ComponentEstimateResult as the canonical API response.
 *
 * Response shape:
 * {
 *   "materials": { cement, sand, steel, brick, concrete, roof_tile },
 *   "cost": { material_cost, labor_cost, total },
 *   "meta": { unit, calculation_version, component, area, house_type, floors, location }
 * }
 */
class ComponentEstimateResource extends JsonResource
{
    /** Remove the default 'data' wrapper so Flutter gets a flat object. */
    public static $wrap = null;

    public function __construct(
        private readonly ComponentEstimateResult    $result,
        private readonly ComponentCalculationInput  $input,
        private readonly string                     $version = 'v1.0',
    ) {
        parent::__construct($result);
    }

    /** @param Request $request */
    public function toArray($request): array
    {
        return [
            'materials' => [
                'cement'    => $this->result->cement,
                'sand'      => $this->result->sand,
                'steel'     => $this->result->steel,
                'brick'     => $this->result->brick,
                'concrete'  => $this->result->concrete,
                'roof_tile' => $this->result->roofTile,
            ],
            'cost' => [
                'material_cost' => round($this->result->materialCost),
                'labor_cost'    => round($this->result->laborCost),
                'total'         => round($this->result->totalCost()),
            ],
            'meta' => [
                'unit'                => 'VND',
                'calculation_version' => $this->version,
                'component'           => $this->input->component->value,
                'area'                => $this->input->area,
                'house_type'          => $this->input->houseType->value,
                'floors'              => $this->input->floors,
                'location'            => $this->input->region->value,
            ],
        ];
    }
}
