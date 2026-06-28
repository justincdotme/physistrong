<?php

declare(strict_types=1);

namespace App\Enums;

enum MeasurementDimension: string
{
    case Weight = 'weight';
    case Distance = 'distance';
    case Speed = 'speed';
}
