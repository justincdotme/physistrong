<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CloneTemplateRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TemplateCloneController extends Controller
{
    use AuthorizesRequests;

    private const WORKOUT_EAGER_LOAD = [
        'exercises',
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

        $workout = DB::transaction(function () use ($request, $template) {
            $workout = Workout::create([
                'name' => $request->validated('name', $template->name),
                'user_id' => $request->user()->id,
                'date' => $request->validated('date'),
            ]);

            foreach ($template->exercises as $exercise) {
                $workout->exercises()->attach($exercise->id, [
                    'exercise_order' => $exercise->pivot->exercise_order,
                ]);

                $workout->entries()->create([
                    'exercise_id' => $exercise->id,
                    'set_order' => $exercise->pivot->exercise_order,
                ]);
            }

            return $workout;
        });

        $workout->load(self::WORKOUT_EAGER_LOAD);

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }
}
