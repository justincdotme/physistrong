<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignTemplateGroupExercisesRequest;
use App\Http\Requests\Api\V1\StoreTemplateEntryGroupRequest;
use App\Http\Resources\Api\V1\WorkoutTemplateResource;
use App\Models\TemplateEntryGroup;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TemplateEntryGroupController extends Controller
{
    use AuthorizesRequests;

    private const TEMPLATE_EAGER_LOAD = ['exercises', 'groups'];

    /**
     * @param StoreTemplateEntryGroupRequest $request
     * @param WorkoutTemplate                $template
     *
     * @return JsonResponse
     */
    public function store(StoreTemplateEntryGroupRequest $request, WorkoutTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $template->groups()->create($request->validated());

        $template->load(self::TEMPLATE_EAGER_LOAD);

        return (new WorkoutTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @param WorkoutTemplate    $template
     * @param TemplateEntryGroup $group
     *
     * @return Response
     */
    public function destroy(WorkoutTemplate $template, TemplateEntryGroup $group): Response
    {
        $this->authorize('update', $template);

        $group->delete();

        return response()->noContent();
    }

    /**
     * @param AssignTemplateGroupExercisesRequest $request
     * @param WorkoutTemplate                     $template
     * @param TemplateEntryGroup                  $group
     *
     * @return WorkoutTemplateResource
     */
    public function assignExercises(
        AssignTemplateGroupExercisesRequest $request,
        WorkoutTemplate $template,
        TemplateEntryGroup $group,
    ): WorkoutTemplateResource {
        $this->authorize('update', $template);

        DB::transaction(function () use ($request, $template, $group): void {
            foreach ($request->validated('exercise_ids') as $exerciseId) {
                $template->exercises()->updateExistingPivot($exerciseId, [
                    'template_entry_group_id' => $group->id,
                ]);
            }
        });

        $template->load(self::TEMPLATE_EAGER_LOAD);

        return new WorkoutTemplateResource($template);
    }
}
