<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogLoadMetric extends Model
{
    /** @var string */
    protected $table = 'log_load_metrics';

    /** @var list<string> */
    protected $fillable = [
        'target_weight',
        'actual_weight',
        'bodyweight_only',
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
            'bodyweight_only' => 'boolean',
            'target_weight'   => 'decimal:2',
            'actual_weight'   => 'decimal:2',
        ];
    }
}
