<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogIntervalHeader extends Model
{
    /** @var string */
    protected $table = 'log_interval_headers';

    /** @var list<string> */
    protected $fillable = [
        'programmed_rounds',
        'completed_rounds',
        'target_work_seconds',
        'target_rest_seconds',
    ];

    /** @return BelongsTo<WorkoutEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(WorkoutEntry::class);
    }

    /** @return HasMany<LogIntervalRound, $this> */
    public function rounds(): HasMany
    {
        return $this->hasMany(LogIntervalRound::class, 'interval_header_id');
    }
}
