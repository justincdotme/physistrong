<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetricDimension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkoutEntry extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'workout_id',
        'exercise_id',
        'entry_group_id',
        'group_round',
        'set_order',
        'notes',
    ];

    /** @return BelongsTo<Workout, $this> */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /** @return BelongsTo<EntryGroup, $this> */
    public function entryGroup(): BelongsTo
    {
        return $this->belongsTo(EntryGroup::class, 'entry_group_id');
    }

    /** @return HasOne<LogLoadMetric, $this> */
    public function loadMetric(): HasOne
    {
        return $this->hasOne(LogLoadMetric::class, 'entry_id');
    }

    /** @return HasOne<LogRepMetric, $this> */
    public function repMetric(): HasOne
    {
        return $this->hasOne(LogRepMetric::class, 'entry_id');
    }

    /** @return HasOne<LogDurationMetric, $this> */
    public function durationMetric(): HasOne
    {
        return $this->hasOne(LogDurationMetric::class, 'entry_id');
    }

    /** @return HasOne<LogDistanceMetric, $this> */
    public function distanceMetric(): HasOne
    {
        return $this->hasOne(LogDistanceMetric::class, 'entry_id');
    }

    /** @return HasOne<LogCardioSetting, $this> */
    public function cardioSetting(): HasOne
    {
        return $this->hasOne(LogCardioSetting::class, 'entry_id');
    }

    /** @return HasOne<LogIntervalHeader, $this> */
    public function intervalHeader(): HasOne
    {
        return $this->hasOne(LogIntervalHeader::class, 'entry_id');
    }

    /** @return HasOne<LogIntensityMetric, $this> */
    public function intensityMetric(): HasOne
    {
        return $this->hasOne(LogIntensityMetric::class, 'entry_id');
    }

    /** @return list<string> */
    public static function metricRelations(): array
    {
        return array_map(
            fn (MetricDimension $d) => $d === MetricDimension::IntervalHeader
                ? 'intervalHeader.rounds'
                : $d->relation(),
            MetricDimension::cases()
        );
    }
}
