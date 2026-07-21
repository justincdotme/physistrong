<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseInterval extends Model
{
    /** @var string */
    protected $table = 'exercise_interval';

    /** @var list<string> */
    protected $fillable = [
        'default_work_seconds',
        'default_rest_seconds',
        'default_rounds',
    ];

    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
