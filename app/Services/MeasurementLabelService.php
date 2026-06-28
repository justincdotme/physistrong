<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MeasurementDimension;
use App\Enums\MeasurementSystem;
use PhpUnitConversion\Unit\Length\KiloMeter;
use PhpUnitConversion\Unit\Length\Mile;
use PhpUnitConversion\Unit\Mass\KiloGram;
use PhpUnitConversion\Unit\Mass\Pound;
use PhpUnitConversion\Unit\Velocity\KiloMeterPerHour;
use PhpUnitConversion\Unit\Velocity\MilesPerHour;

class MeasurementLabelService
{
    public function symbol(MeasurementSystem $system, MeasurementDimension $dimension): string
    {
        $unitClass = match ([$system, $dimension]) {
            [MeasurementSystem::Imperial, MeasurementDimension::Weight] => Pound::class,
            [MeasurementSystem::Metric, MeasurementDimension::Weight] => KiloGram::class,
            [MeasurementSystem::Imperial, MeasurementDimension::Distance] => Mile::class,
            [MeasurementSystem::Metric, MeasurementDimension::Distance] => KiloMeter::class,
            [MeasurementSystem::Imperial, MeasurementDimension::Speed] => MilesPerHour::class,
            [MeasurementSystem::Metric, MeasurementDimension::Speed] => KiloMeterPerHour::class,
        };

        return (new $unitClass())->getSymbol();
    }
}
