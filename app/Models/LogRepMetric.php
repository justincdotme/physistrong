<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogRepMetric extends Model
{
    /** @var string */
    protected $table = 'log_rep_metrics';

    /** @var list<string> */
    protected $fillable = [
        'target_reps',
        'actual_reps',
        'to_failure',
        'failure_rep',
    ];

    /** @return BelongsTo<WorkoutEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(WorkoutEntry::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'to_failure' => 'boolean',
        ];
    }
}
