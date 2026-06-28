<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExerciseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Pivots\ExerciseWorkoutPivot;

/**
 * @property ExerciseType $type
 * @property ExerciseWorkoutPivot|null $pivot
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
}
