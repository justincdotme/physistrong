<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntryGroup extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'workout_id',
        'name',
        'planned_rounds',
        'rest_between_exercises_seconds',
        'rest_between_rounds_seconds',
    ];

    /** @return BelongsTo<Workout, $this> */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /** @return HasMany<WorkoutEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(WorkoutEntry::class, 'entry_group_id');
    }
}
