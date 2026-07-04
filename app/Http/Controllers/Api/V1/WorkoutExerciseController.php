<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AttachExerciseRequest;
use App\Http\Requests\Api\V1\ReorderWorkoutExercisesRequest;
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

    public function attach(AttachExerciseRequest $request, Workout $workout): JsonResponse
    {
        $this->authorize('update', $workout);

        $exerciseId = $request->validated('exercise_id');

        $attached = DB::transaction(function () use ($workout, $exerciseId): bool {
            // Discarded read: holding the parent row serializes concurrent
            // attach/reorder so two attaches cannot compute the same max order.
            Workout::whereKey($workout->id)->lockForUpdate()->first();

            if ($workout->exercises()->where('exercises.id', $exerciseId)->exists()) {
                return false;
            }

            $nextOrder = $workout->exercises()->max('exercise_order') ?? -1;
            $nextOrder++;

            $workout->exercises()->attach($exerciseId, ['exercise_order' => $nextOrder]);

            return true;
        });

        if (! $attached) {
            return response()->json([
                'message' => 'Exercise is already attached to this workout.',
            ], 409);
        }

        $workout->load(Workout::detailRelations());

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

    public function reorder(ReorderWorkoutExercisesRequest $request, Workout $workout): WorkoutResource
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $workout) {
            Workout::whereKey($workout->id)->lockForUpdate()->first();

            $ids = $request->validated('ids');
            foreach ($ids as $index => $id) {
                $workout->exercises()->updateExistingPivot($id, ['exercise_order' => $index]);
            }
        });

        $workout->load(Workout::detailRelations());

        return new WorkoutResource($workout);
    }
}
