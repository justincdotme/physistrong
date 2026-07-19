<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CloneWorkoutTemplateRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use App\Services\WorkoutCloneService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class WorkoutTemplateCloneController extends Controller
{
    use AuthorizesRequests;

    /** @param WorkoutCloneService $cloneService */
    public function __construct(private WorkoutCloneService $cloneService) {}

    /**
     * @param CloneWorkoutTemplateRequest $request
     * @param WorkoutTemplate             $template
     *
     * @return JsonResponse
     */
    public function __invoke(CloneWorkoutTemplateRequest $request, WorkoutTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        /** @var User $user */
        $user = $request->user();

        $workout = $this->cloneService->fromTemplate($template, $user, $request->validated());

        $workout->load(Workout::detailRelations());

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }
}
