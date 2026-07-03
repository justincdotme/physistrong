<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Pivots\ExerciseWorkoutPivot;
use Database\Factories\WorkoutFactory;
use Illuminate\Support\Carbon;

/** @property Carbon $date */
class Workout extends Model
{
    /** @use HasFactory<WorkoutFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'user_id',
        'date',
        'exhaustion',
        'soreness',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'exhaustion' => 'integer',
            'soreness' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Exercise, $this, ExerciseWorkoutPivot, 'pivot'> */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class)
            ->using(ExerciseWorkoutPivot::class)
            ->withPivot('exercise_order')
            ->orderByPivot('exercise_order');
    }

    /** @return HasMany<WorkoutEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(WorkoutEntry::class);
    }

    /** @return HasMany<EntryGroup, $this> */
    public function groups(): HasMany
    {
        return $this->hasMany(EntryGroup::class);
    }

    /** @return list<string> */
    public static function detailRelations(): array
    {
        $entryMetrics = array_map(
            fn (string $r) => "entries.{$r}",
            WorkoutEntry::metricRelations()
        );

        return array_merge(
            ['exercises', 'groups', 'entries.exercise'],
            $entryMetrics
        );
    }
}
