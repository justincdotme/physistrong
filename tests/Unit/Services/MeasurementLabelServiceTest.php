<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MeasurementDimension;
use App\Enums\MeasurementSystem;
use App\Services\MeasurementLabelService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MeasurementLabelServiceTest extends TestCase
{
    private MeasurementLabelService $service;

    protected function setUp(): void
    {
        $this->service = new MeasurementLabelService();
    }

    #[DataProvider('symbolProvider')]
    public function test_returns_correct_symbol(
        MeasurementSystem $system,
        MeasurementDimension $dimension,
        string $expected,
    ): void {
        $symbol = $this->service->symbol($system, $dimension);
        $this->assertSame($expected, $symbol);
    }

    /**
     * @return array<string, array{MeasurementSystem, MeasurementDimension, string}>
     */
    public static function symbolProvider(): array
    {
        return [
            'imperial weight' => [MeasurementSystem::Imperial, MeasurementDimension::Weight, 'lb'],
            'metric weight' => [MeasurementSystem::Metric, MeasurementDimension::Weight, 'kg'],
            'imperial distance' => [MeasurementSystem::Imperial, MeasurementDimension::Distance, 'mi'],
            'metric distance' => [MeasurementSystem::Metric, MeasurementDimension::Distance, 'km'],
            'imperial speed' => [MeasurementSystem::Imperial, MeasurementDimension::Speed, 'mph'],
            'metric speed' => [MeasurementSystem::Metric, MeasurementDimension::Speed, 'km/h'],
        ];
    }
}
