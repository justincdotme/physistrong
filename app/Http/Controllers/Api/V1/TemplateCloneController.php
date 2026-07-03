<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CloneTemplateRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\User;
use App\Models\WorkoutTemplate;
use App\Services\WorkoutCloneService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

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

    public function __construct(private WorkoutCloneService $cloneService) {}

    public function __invoke(CloneTemplateRequest $request, WorkoutTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        /** @var User $user */
        $user = $request->user();

        $workout = $this->cloneService->fromTemplate($template, $user, $request->validated());

        $workout->load(self::WORKOUT_EAGER_LOAD);

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }
}
