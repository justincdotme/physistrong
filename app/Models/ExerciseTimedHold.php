<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseTimedHold extends Model
{
    protected $table = 'exercise_timed_hold';

    /** @var list<string> */
    protected $fillable = [
        'target_duration_seconds',
    ];

    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
