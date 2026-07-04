<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExerciseType;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property ExerciseType $type
 * @property mixed $pivot
 */
class Exercise extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'type',
        'user_id',
        'equipment_type_id',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ExerciseType::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<EquipmentType, $this> */
    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    /** @return HasOne<ExerciseResistance, $this> */
    public function resistance(): HasOne
    {
        return $this->hasOne(ExerciseResistance::class);
    }

    /** @return HasOne<ExerciseTimedHold, $this> */
    public function timedHold(): HasOne
    {
        return $this->hasOne(ExerciseTimedHold::class);
    }

    /** @return HasOne<ExerciseDistance, $this> */
    public function distance(): HasOne
    {
        return $this->hasOne(ExerciseDistance::class);
    }

    /** @return HasOne<ExerciseInterval, $this> */
    public function interval(): HasOne
    {
        return $this->hasOne(ExerciseInterval::class);
    }

    /** @return BelongsToMany<Workout, $this> */
    public function workouts(): BelongsToMany
    {
        return $this->belongsToMany(Workout::class, 'exercise_workout', 'exercise_id', 'workout_id');
    }

    /** @return HasMany<WorkoutEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(WorkoutEntry::class);
    }

    /** @return BelongsToMany<WorkoutTemplate, $this> */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(WorkoutTemplate::class, 'template_exercises', 'exercise_id', 'template_id');
    }

    /**
     * Usage annotations read by ExerciseResource and the delete guard.
     * usage_count sums workout and template references; entry-only usage
     * surfaces through has_logged_data.
     *
     * @param  Builder<Exercise>  $query
     * @return Builder<Exercise>
     */
    public function scopeWithUsage(Builder $query): Builder
    {
        return $query
            ->withCount(['workouts', 'templates'])
            ->withExists(['entries as has_logged_data']);
    }

    public function isInUse(): bool
    {
        return $this->workouts()->exists()
            || $this->entries()->exists()
            || $this->templates()->exists();
    }

    /**
     * System rows (user_id null) visible to all; user rows only to their owner.
     * Query closure and instance predicate must change together.
     */
    public static function visibilityConstraint(User $user): Closure
    {
        return fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id);
    }

    /**
     * @param  Builder<Exercise>  $query
     * @return Builder<Exercise>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(static::visibilityConstraint($user));
    }

    public function isVisibleTo(User $user): bool
    {
        return $this->user_id === null || $this->user_id === $user->id;
    }
}
