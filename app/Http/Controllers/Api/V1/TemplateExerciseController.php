<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AttachExerciseRequest;
use App\Http\Requests\Api\V1\ReorderTemplateExercisesRequest;
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

    /**
     * @param AttachExerciseRequest $request
     * @param WorkoutTemplate       $template
     *
     * @return JsonResponse
     */
    public function attach(AttachExerciseRequest $request, WorkoutTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $exerciseId = $request->validated('exercise_id');

        $attached = DB::transaction(function () use ($template, $exerciseId): bool {
            WorkoutTemplate::whereKeyLocked($template->id)->first();

            if ($template->exercises()->where('exercises.id', $exerciseId)->exists()) {
                return false;
            }

            $nextOrder = (int) ($template->exercises()->max('exercise_order') ?? -1);
            $nextOrder++;

            $template->exercises()->attach($exerciseId, ['exercise_order' => $nextOrder]);

            return true;
        });

        if (! $attached) {
            return response()->json([
                'message' => 'Exercise is already attached to this template.',
            ], 409);
        }

        $template->load(self::SHOW_EAGER_LOAD);

        return (new WorkoutTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @param WorkoutTemplate $template
     * @param Exercise        $exercise
     *
     * @return Response
     */
    public function detach(WorkoutTemplate $template, Exercise $exercise): Response
    {
        $this->authorize('update', $template);

        $template->exercises()->detach($exercise->id);

        return response()->noContent();
    }

    /**
     * @param ReorderTemplateExercisesRequest $request
     * @param WorkoutTemplate                 $template
     *
     * @return WorkoutTemplateResource
     */
    public function reorder(ReorderTemplateExercisesRequest $request, WorkoutTemplate $template): WorkoutTemplateResource
    {
        $this->authorize('update', $template);

        DB::transaction(function () use ($request, $template): void {
            WorkoutTemplate::whereKeyLocked($template->id)->first();

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
