<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CopyWorkoutRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkoutCopyController extends Controller
{
    use AuthorizesRequests;

    private const WORKOUT_EAGER_LOAD = [
        'exercises',
        'groups',
        'entries.exercise',
        'entries.loadMetric',
        'entries.repMetric',
        'entries.durationMetric',
        'entries.distanceMetric',
        'entries.cardioSetting',
        'entries.intervalHeader.rounds',
        'entries.intensityMetric',
    ];

    public function __invoke(CopyWorkoutRequest $request, Workout $workout): JsonResponse
    {
        $this->authorize('view', $workout);

        $workout->load([
            'exercises',
            'groups',
            'entries.loadMetric',
            'entries.repMetric',
            'entries.durationMetric',
            'entries.distanceMetric',
            'entries.cardioSetting',
            'entries.intervalHeader',
        ]);

        $newWorkout = DB::transaction(function () use ($request, $workout) {
            $newWorkout = Workout::create([
                'name' => $request->validated('name', $workout->name),
                'user_id' => $request->user()->id,
                'date' => $request->validated('date'),
            ]);

            $groupMapping = [];
            foreach ($workout->groups as $group) {
                $newGroup = $newWorkout->groups()->create([
                    'name' => $group->name,
                    'planned_rounds' => $group->planned_rounds,
                    'rest_between_exercises_seconds' => $group->rest_between_exercises_seconds,
                    'rest_between_rounds_seconds' => $group->rest_between_rounds_seconds,
                ]);
                $groupMapping[$group->id] = $newGroup->id;
            }

            foreach ($workout->exercises as $exercise) {
                $newWorkout->exercises()->attach($exercise->id, [
                    'exercise_order' => $exercise->pivot->exercise_order,
                ]);
            }

            foreach ($workout->entries as $entry) {
                $newEntry = $newWorkout->entries()->create([
                    'exercise_id' => $entry->exercise_id,
                    'set_order' => $entry->set_order,
                    'entry_group_id' => $entry->entry_group_id
                        ? ($groupMapping[$entry->entry_group_id] ?? null)
                        : null,
                    'group_round' => $entry->group_round,
                    'notes' => $entry->notes,
                ]);

                $this->cloneMetrics($entry, $newEntry);
            }

            return $newWorkout;
        });

        $newWorkout->load(self::WORKOUT_EAGER_LOAD);

        return (new WorkoutResource($newWorkout))
            ->response()
            ->setStatusCode(201);
    }

    private function cloneMetrics(WorkoutEntry $source, WorkoutEntry $target): void
    {
        if ($source->loadMetric) {
            $target->loadMetric()->create([
                'target_weight' => $source->loadMetric->target_weight,
                'bodyweight_only' => $source->loadMetric->bodyweight_only,
            ]);
        }

        if ($source->repMetric) {
            $target->repMetric()->create([
                'target_reps' => $source->repMetric->target_reps,
                'to_failure' => $source->repMetric->to_failure,
            ]);
        }

        if ($source->durationMetric) {
            $target->durationMetric()->create([
                'target_duration_seconds' => $source->durationMetric->target_duration_seconds,
            ]);
        }

        if ($source->distanceMetric) {
            $target->distanceMetric()->create([
                'target_distance' => $source->distanceMetric->target_distance,
                'distance_unit' => $source->distanceMetric->distance_unit,
            ]);
        }

        if ($source->cardioSetting) {
            $target->cardioSetting()->create([
                'resistance_level' => $source->cardioSetting->resistance_level,
                'incline' => $source->cardioSetting->incline,
                'speed' => $source->cardioSetting->speed,
                'cadence' => $source->cardioSetting->cadence,
            ]);
        }

        if ($source->intervalHeader) {
            $target->intervalHeader()->create([
                'programmed_rounds' => $source->intervalHeader->programmed_rounds,
                'target_work_seconds' => $source->intervalHeader->target_work_seconds,
                'target_rest_seconds' => $source->intervalHeader->target_rest_seconds,
            ]);
        }
    }
}
