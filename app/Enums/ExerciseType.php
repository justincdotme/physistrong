<?php

declare(strict_types=1);

namespace App\Enums;

enum ExerciseType: string
{
    case Resistance = 'resistance';
    case TimedHold = 'timed_hold';
    case Distance = 'distance';
    case Interval = 'interval';
}
