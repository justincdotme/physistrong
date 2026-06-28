<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DistanceUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogDistanceMetric extends Model
{
    protected $table = 'log_distance_metrics';

    /** @var list<string> */
    protected $fillable = [
        'entry_id',
        'target_distance',
        'actual_distance',
        'distance_unit',
        'lap_count',
        'stroke_count',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'distance_unit' => DistanceUnit::class,
            'target_distance' => 'decimal:2',
            'actual_distance' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<WorkoutEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(WorkoutEntry::class);
    }
}
