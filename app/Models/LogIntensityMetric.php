<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogIntensityMetric extends Model
{
    protected $table = 'log_intensity_metrics';

    /** @var list<string> */
    protected $fillable = [
        'entry_id',
        'rpe',
        'avg_hr',
        'max_hr',
    ];

    /** @return BelongsTo<WorkoutEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(WorkoutEntry::class);
    }
}
