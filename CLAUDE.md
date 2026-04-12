# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Setup (first time)
composer run setup

# Run all tests
composer run test

# Run a single test file
php artisan test tests/Feature/Api/ComponentCalculationTest.php

# Run a single test method
php artisan test --filter test_calculates_roof_main

# Format PHP (Laravel Pint)
./vendor/bin/pint

# Dev server with queue + logs + vite
composer run dev
```

## Architecture

**BuildHome API** is a Laravel 13 / PHP 8.3 JSON API that calculates Vietnamese house construction material quantities and costs. It is consumed by a Flutter client (`/Users/khanhho/buildhome_flutter`). The Flutter app ships identical formulas locally as a fallback; both sides must produce the same numbers.

### Request flow

```
POST /api/v1/component/calculate
  → ComponentCalculationRequest   (validates + enums)
  → ComponentCalculationController::calculate()
      → Cache::remember(6h, cacheKey)
          → PricingService($region)         (unit prices in VND + regional multiplier)
          → MaterialCalculatorService       (formulas per ComponentType)
              → ComponentEstimateResult DTO
  → ComponentEstimateResource               (flat JSON, no 'data' wrapper)
```

### Key domain objects

| Class | Role |
|---|---|
| `ComponentType` (enum) | 9 component slugs (`roof_main`, `wall_front`, etc.) with Vietnamese labels and group |
| `HouseType` (enum) | House style variants |
| `Region` (enum) | `ho_chi_minh`, `hanoi`, `mien_trung`, `rural_vietnam`, `default` — each has a price multiplier and optional per-material overrides |
| `ComponentCalculationInput` (readonly DTO) | Immutable input; generates cache key |
| `ComponentEstimateResult` (readonly DTO) | Quantities + costs; `totalCost()` sums material + labour |
| `PricingService` | All unit prices in VND; applies `Region::multiplier()` and `Region::cementPriceOverride()` |
| `MaterialCalculatorService` | One `calculate()` dispatch + private formula per component type |

### Response shape

```json
{
  "materials": { "cement": 0, "sand": 0, "steel": 0, "brick": 0, "concrete": 0, "roof_tile": 0 },
  "cost":      { "material_cost": 0, "labor_cost": 0, "total": 0 },
  "meta":      { "unit": "VND", "calculation_version": "v1.0", "component": "...", "area": 0, "house_type": "...", "floors": 1, "location": "default" }
}
```

No `data` wrapper — `ComponentEstimateResource::$wrap = null`.

### Adding a new component

1. Add case to `ComponentType` with `label()` and `group()` arms.
2. Add private formula method in `MaterialCalculatorService`.
3. Add match arm in `MaterialCalculatorService::calculate()`.
4. Add the slug to the `componentProvider` data provider in `ComponentCalculationTest`.

### Cache

Results cached 6 hours by `(component, area, house_type, floors, region)` key via `ComponentCalculationInput::cacheKey()`. Cache driver is whatever `.env` configures (SQLite for local dev).
