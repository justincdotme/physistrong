<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AttachExerciseRequest;
use App\Http\Requests\Api\V1\ReorderRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WorkoutExerciseController extends Controller
{
    use AuthorizesRequests;

    private const SHOW_EAGER_LOAD = [
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

    public function attach(AttachExerciseRequest $request, Workout $workout): JsonResponse
    {
        $this->authorize('update', $workout);

        $exerciseId = $request->validated('exercise_id');

        if ($workout->exercises()->where('exercises.id', $exerciseId)->exists()) {
            return response()->json([
                'message' => 'Exercise is already attached to this workout.',
            ], 409);
        }

        $nextOrder = $workout->exercises()->max('exercise_order') ?? -1;
        $nextOrder++;

        $workout->exercises()->attach($exerciseId, ['exercise_order' => $nextOrder]);
        $workout->load(self::SHOW_EAGER_LOAD);

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }

    public function detach(Workout $workout, Exercise $exercise): Response
    {
        $this->authorize('update', $workout);

        $workout->entries()->where('exercise_id', $exercise->id)->delete();
        $workout->exercises()->detach($exercise->id);

        return response()->noContent();
    }

    public function reorder(ReorderRequest $request, Workout $workout): WorkoutResource
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $workout) {
            $ids = $request->validated('ids');
            foreach ($ids as $index => $id) {
                $workout->exercises()->updateExistingPivot($id, ['exercise_order' => $index]);
            }
        });

        $workout->load(self::SHOW_EAGER_LOAD);

        return new WorkoutResource($workout);
    }
}
