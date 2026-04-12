<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ComponentCalculationTest extends TestCase
{
    use RefreshDatabase;

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_calculates_roof_main(): void
    {
        $response = $this->postJson('/api/v1/component/calculate', [
            'component'  => 'roof_main',
            'area'       => 120.0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
            'location'   => 'rural_vietnam',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'materials' => ['cement', 'sand', 'steel', 'brick', 'concrete', 'roof_tile'],
                'cost'      => ['material_cost', 'labor_cost', 'total'],
                'meta'      => ['unit', 'calculation_version', 'component', 'area'],
            ]);

        $data = $response->json();
        $this->assertEquals('roof_main', $data['meta']['component']);
        $this->assertEquals(120.0, $data['meta']['area']);
        $this->assertEquals('VND', $data['meta']['unit']);
        $this->assertGreaterThan(0, $data['materials']['roof_tile']);
        $this->assertGreaterThan(0, $data['cost']['total']);
        // Roof tile = 120 × 22 = 2640
        $this->assertEquals(2640, $data['materials']['roof_tile']);
    }

    public function test_calculates_wall(): void
    {
        $response = $this->postJson('/api/v1/component/calculate', [
            'component'  => 'wall_front',
            'area'       => 80.0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
        ]);

        $response->assertOk();
        $data = $response->json();
        // Bricks: 80 × 60 = 4800
        $this->assertEquals(4800, $data['materials']['brick']);
        $this->assertEquals(0, $data['materials']['roof_tile']);
    }

    public function test_calculates_foundation(): void
    {
        $response = $this->postJson('/api/v1/component/calculate', [
            'component'  => 'foundation',
            'area'       => 32.0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
        ]);

        $response->assertOk();
        $data = $response->json();
        // Concrete: 32 × 0.5 = 16 m³
        $this->assertEquals(16, $data['materials']['concrete']);
        // Steel: 32 × 90 = 2880 kg
        $this->assertEquals(2880, $data['materials']['steel']);
    }

    public function test_calculates_door(): void
    {
        $response = $this->postJson('/api/v1/component/calculate', [
            'component'  => 'door_main',
            'area'       => 1.89,
            'house_type' => 'thai_roof',
            'floors'     => 1,
        ]);

        $response->assertOk();
        $data = $response->json();
        // All material quantities are 0 for door — cost only
        $this->assertEquals(0, $data['materials']['cement']);
        $this->assertGreaterThan(0, $data['cost']['material_cost']);
    }

    public function test_regional_pricing_applied(): void
    {
        $hcm = $this->postJson('/api/v1/component/calculate', [
            'component'  => 'wall_front',
            'area'       => 50.0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
            'location'   => 'ho_chi_minh',
        ])->json('cost.total');

        $rural = $this->postJson('/api/v1/component/calculate', [
            'component'  => 'wall_front',
            'area'       => 50.0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
            'location'   => 'rural_vietnam',
        ])->json('cost.total');

        // HCM has multiplier 1.15, rural 0.90 — HCM must be more expensive
        $this->assertGreaterThan($rural, $hcm);
    }

    public function test_result_is_cached(): void
    {
        Cache::flush();

        $payload = [
            'component'  => 'roof_main',
            'area'       => 60.0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
            'location'   => 'default',
        ];

        $first  = $this->postJson('/api/v1/component/calculate', $payload)->json('cost.total');
        $second = $this->postJson('/api/v1/component/calculate', $payload)->json('cost.total');

        $this->assertEquals($first, $second);
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'version' => 'v1.0']);
    }

    // ── Validation errors ─────────────────────────────────────────────────────

    public function test_rejects_unknown_component(): void
    {
        $this->postJson('/api/v1/component/calculate', [
            'component'  => 'flying_carpet',
            'area'       => 10.0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
        ])->assertUnprocessable();
    }

    public function test_rejects_zero_area(): void
    {
        $this->postJson('/api/v1/component/calculate', [
            'component'  => 'wall_front',
            'area'       => 0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
        ])->assertUnprocessable();
    }

    public function test_rejects_missing_house_type(): void
    {
        $this->postJson('/api/v1/component/calculate', [
            'component' => 'wall_front',
            'area'      => 50.0,
            'floors'    => 1,
        ])->assertUnprocessable();
    }

    public function test_rejects_floors_out_of_range(): void
    {
        $this->postJson('/api/v1/component/calculate', [
            'component'  => 'wall_front',
            'area'       => 50.0,
            'house_type' => 'thai_roof',
            'floors'     => 10,
        ])->assertUnprocessable();
    }

    // ── All component types ───────────────────────────────────────────────────

    #[DataProvider('componentProvider')]
    public function test_all_component_types_return_200(string $component): void
    {
        $this->postJson('/api/v1/component/calculate', [
            'component'  => $component,
            'area'       => 20.0,
            'house_type' => 'thai_roof',
            'floors'     => 1,
        ])->assertOk();
    }

    public static function componentProvider(): array
    {
        return [
            ['roof_main'],
            ['roof_sub'],
            ['wall_front'],
            ['door_main'],
            ['window_front'],
            ['column'],
            ['foundation'],
            ['balcony'],
            ['garden'],
        ];
    }
}
