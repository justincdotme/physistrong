<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExerciseType;
use Closure;
use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property ExerciseType $type
 * @property mixed        $pivot
 */
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'type',
        'user_id',
        'equipment_type_id',
        'notes',
    ];

    /**
     * System rows (user_id null) visible to all; user rows only to their owner.
     * Query closure and instance predicate must change together.
     *
     * @param User $user
     *
     * @return Closure
     */
    public static function visibilityConstraint(User $user): Closure
    {
        return fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id);
    }

    /**
     * @param User $user
     *
     * @return array<string, Closure>
     */
    private static function usageCounts(User $user): array
    {
        return [
            'workouts'  => fn (Builder $query) => $query->where('workouts.user_id', $user->id),
            'templates' => fn (Builder $query) => $query->where('workout_templates.user_id', $user->id),
        ];
    }

    /**
     * Entries carry no owner of their own, so ownership is read off the parent
     * workout.
     *
     * @param User $user
     *
     * @return array<string, Closure>
     */
    private static function loggedDataExists(User $user): array
    {
        return [
            'entries as has_logged_data' => fn (Builder $query) => $query->whereHas(
                'workout',
                fn (Builder $workout) => $workout->where('workouts.user_id', $user->id),
            ),
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
     * Scoped to one user so a system exercise never reports somebody else's
     * workouts, templates, or logged entries.
     *
     * @param Builder<Exercise> $query
     * @param User              $user
     *
     * @return Builder<Exercise>
     */
    public function scopeWithUsage(Builder $query, User $user): Builder
    {
        return $query
            ->withCount(self::usageCounts($user))
            ->withExists(self::loggedDataExists($user));
    }

    /**
     * @param User $user
     *
     * @return static
     */
    public function loadUsage(User $user): static
    {
        return $this
            ->loadCount(self::usageCounts($user))
            ->loadExists(self::loggedDataExists($user));
    }

    /**
     * @return boolean
     */
    public function isInUse(): bool
    {
        return $this->workouts()->exists()
            || $this->entries()->exists()
            || $this->templates()->exists();
    }

    /**
     * @param Builder<Exercise> $query
     * @param User              $user
     *
     * @return Builder<Exercise>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(static::visibilityConstraint($user));
    }

    /**
     * @param User $user
     *
     * @return boolean
     */
    public function isVisibleTo(User $user): bool
    {
        return $this->user_id === null || $this->user_id === $user->id;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ExerciseType::class,
        ];
    }
}
