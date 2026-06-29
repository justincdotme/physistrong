<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

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

        if ($this->relationLoaded('loadMetric') && $this->loadMetric) {
            $metrics['load'] = collect($this->loadMetric->toArray())
                ->except(['id', 'entry_id', 'created_at', 'updated_at'])
                ->all();
        }

        if ($this->relationLoaded('repMetric') && $this->repMetric) {
            $metrics['reps'] = collect($this->repMetric->toArray())
                ->except(['id', 'entry_id', 'created_at', 'updated_at'])
                ->all();
        }

        if ($this->relationLoaded('durationMetric') && $this->durationMetric) {
            $metrics['duration'] = collect($this->durationMetric->toArray())
                ->except(['id', 'entry_id', 'created_at', 'updated_at'])
                ->all();
        }

        if ($this->relationLoaded('distanceMetric') && $this->distanceMetric) {
            $metrics['distance'] = collect($this->distanceMetric->toArray())
                ->except(['id', 'entry_id', 'created_at', 'updated_at'])
                ->all();
        }

        if ($this->relationLoaded('cardioSetting') && $this->cardioSetting) {
            $metrics['cardio_settings'] = collect($this->cardioSetting->toArray())
                ->except(['id', 'entry_id', 'created_at', 'updated_at'])
                ->all();
        }

        if ($this->relationLoaded('intensityMetric') && $this->intensityMetric) {
            $metrics['intensity'] = collect($this->intensityMetric->toArray())
                ->except(['id', 'entry_id', 'created_at', 'updated_at'])
                ->all();
        }

        if ($this->relationLoaded('intervalHeader') && $this->intervalHeader) {
            $header = collect($this->intervalHeader->toArray())
                ->except(['id', 'entry_id', 'created_at', 'updated_at'])
                ->all();
            if ($this->intervalHeader->relationLoaded('rounds')) {
                $header['rounds'] = $this->intervalHeader->rounds->map(
                    fn ($round) => collect($round->toArray())
                        ->except(['id', 'interval_header_id', 'created_at', 'updated_at'])
                        ->all()
                )->all();
            }
            $metrics['interval_header'] = $header;
        }

        return $metrics;
    }
}
