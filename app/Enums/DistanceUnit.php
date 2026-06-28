<?php

declare(strict_types=1);

namespace App\Enums;

enum DistanceUnit: string
{
    case Meters = 'meters';
    case Kilometers = 'kilometers';
    case Miles = 'miles';
    case Yards = 'yards';
}
