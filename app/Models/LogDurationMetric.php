<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogDurationMetric extends Model
{
    protected $table = 'log_duration_metrics';

    /** @var list<string> */
    protected $fillable = [
        'target_duration_seconds',
        'actual_duration_seconds',
    ];

    /** @return BelongsTo<WorkoutEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(WorkoutEntry::class);
    }
}
