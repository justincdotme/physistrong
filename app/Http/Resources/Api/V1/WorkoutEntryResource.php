<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\MetricDimension;
use App\Models\WorkoutEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkoutEntry */
class WorkoutEntryResource extends JsonResource
{
    private const ROUND_COLUMNS = [
        'round_number',
        'actual_work_seconds',
        'actual_rest_seconds',
        'heart_rate_avg',
        'heart_rate_peak',
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_id' => $this->workout_id,
            'exercise_id' => $this->exercise_id,
            'set_order' => $this->set_order,
            'entry_group_id' => $this->entry_group_id,
            'group_round' => $this->group_round,
            'notes' => $this->notes,
            'exercise' => $this->whenLoaded('exercise', fn () => new ExerciseSummaryResource($this->exercise)),
            'metrics' => $this->metricData(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /** @return array<string, mixed> */
    private function metricData(): array
    {
        $metrics = [];

        foreach (MetricDimension::cases() as $dimension) {
            $relation = $dimension->relation();

            if (! $this->relationLoaded($relation) || ! $this->$relation) {
                continue;
            }

            $payload = $this->$relation->only($dimension->apiColumns());

            if ($dimension === MetricDimension::IntervalHeader && $this->intervalHeader->relationLoaded('rounds')) {
                $payload['rounds'] = $this->intervalHeader->rounds->map(
                    fn ($round) => $round->only(self::ROUND_COLUMNS)
                )->all();
            }

            $metrics[$dimension->value] = $payload;
        }

        return $metrics;
    }
}
