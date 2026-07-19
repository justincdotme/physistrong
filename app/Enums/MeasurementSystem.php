<?php

declare(strict_types=1);

namespace App\Enums;

enum MeasurementSystem: string
{
    case Imperial = 'imperial';
    case Metric   = 'metric';
}
