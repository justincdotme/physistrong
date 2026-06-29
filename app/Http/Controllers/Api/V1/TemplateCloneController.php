<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CloneTemplateRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TemplateCloneController extends Controller
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

    public function __invoke(CloneTemplateRequest $request, WorkoutTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $template->load(['exercises', 'groups']);

        $workout = DB::transaction(function () use ($request, $template) {
            $workout = Workout::create([
                'name' => $request->validated('name', $template->name),
                'user_id' => $request->user()->id,
                'date' => $request->validated('date'),
            ]);

            $groupMapping = [];
            foreach ($template->groups as $templateGroup) {
                $workoutGroup = $workout->groups()->create([
                    'name' => $templateGroup->name,
                    'planned_rounds' => $templateGroup->planned_rounds,
                    'rest_between_exercises_seconds' => $templateGroup->rest_between_exercises_seconds,
                    'rest_between_rounds_seconds' => $templateGroup->rest_between_rounds_seconds,
                ]);
                $groupMapping[$templateGroup->id] = $workoutGroup->id;
            }

            /** @var Collection<int, Exercise> $exercises */
            $exercises = $template->exercises;

            foreach ($exercises as $exercise) {
                $workout->exercises()->attach($exercise->id, [
                    'exercise_order' => $exercise->pivot->exercise_order,
                ]);
            }

            $processedGroups = [];
            $setOrder = 0;

            foreach ($exercises as $exercise) {
                $templateGroupId = $exercise->pivot->template_entry_group_id;

                if ($templateGroupId === null) {
                    $workout->entries()->create([
                        'exercise_id' => $exercise->id,
                        'set_order' => $setOrder++,
                    ]);
                } elseif (! in_array($templateGroupId, $processedGroups, true)) {
                    $processedGroups[] = $templateGroupId;
                    $templateGroup = $template->groups->firstWhere('id', $templateGroupId);
                    $workoutGroupId = $groupMapping[$templateGroupId];

                    $groupExercises = $exercises
                        ->filter(fn (Exercise $e) => $e->pivot->template_entry_group_id === $templateGroupId)
                        ->sortBy(fn (Exercise $e) => $e->pivot->exercise_order);

                    for ($round = 1; $round <= $templateGroup->planned_rounds; $round++) {
                        foreach ($groupExercises as $groupExercise) {
                            $workout->entries()->create([
                                'exercise_id' => $groupExercise->id,
                                'set_order' => $setOrder++,
                                'entry_group_id' => $workoutGroupId,
                                'group_round' => $round,
                            ]);
                        }
                    }
                }
            }

            return $workout;
        });

        $workout->load(self::WORKOUT_EAGER_LOAD);

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }
}
