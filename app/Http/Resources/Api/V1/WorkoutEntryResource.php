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
            'exercise' => $this->whenLoaded('exercise', fn () => [
                'id' => $this->exercise->id,
                'name' => $this->exercise->name,
                'type' => $this->exercise->type,
            ]),
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

            $payload = collect($this->$relation->toArray())
                ->except(['id', 'entry_id', 'created_at', 'updated_at'])
                ->all();

            if ($dimension === MetricDimension::IntervalHeader && $this->intervalHeader->relationLoaded('rounds')) {
                $payload['rounds'] = $this->intervalHeader->rounds->map(
                    fn ($round) => collect($round->toArray())
                        ->except(['id', 'interval_header_id', 'created_at', 'updated_at'])
                        ->all()
                )->all();
            }

            $metrics[$dimension->value] = $payload;
        }

        return $metrics;
    }
}
