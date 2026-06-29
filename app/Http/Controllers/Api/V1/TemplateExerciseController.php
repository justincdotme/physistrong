<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AttachExerciseRequest;
use App\Http\Requests\Api\V1\ReorderRequest;
use App\Http\Resources\Api\V1\WorkoutTemplateResource;
use App\Models\Exercise;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TemplateExerciseController extends Controller
{
    use AuthorizesRequests;

    private const SHOW_EAGER_LOAD = ['exercises', 'groups'];

    public function attach(AttachExerciseRequest $request, WorkoutTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $exerciseId = $request->validated('exercise_id');

        if ($template->exercises()->where('exercises.id', $exerciseId)->exists()) {
            return response()->json([
                'message' => 'Exercise is already attached to this template.',
            ], 409);
        }

        $nextOrder = (int) ($template->exercises()->max('exercise_order') ?? -1);
        $nextOrder++;

        $template->exercises()->attach($exerciseId, ['exercise_order' => $nextOrder]);
        $template->load(self::SHOW_EAGER_LOAD);

        return (new WorkoutTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function detach(WorkoutTemplate $template, Exercise $exercise): Response
    {
        $this->authorize('update', $template);

        $template->exercises()->detach($exercise->id);

        return response()->noContent();
    }

    public function reorder(ReorderRequest $request, WorkoutTemplate $template): WorkoutTemplateResource
    {
        $this->authorize('update', $template);

        DB::transaction(function () use ($request, $template) {
            /** @var array<int, int> $ids */
            $ids = $request->validated('ids');
            foreach ($ids as $index => $id) {
                $template->exercises()->updateExistingPivot($id, ['exercise_order' => $index]);
            }
        });

        $template->load(self::SHOW_EAGER_LOAD);

        return new WorkoutTemplateResource($template);
    }
}
