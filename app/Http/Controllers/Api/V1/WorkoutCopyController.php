<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CopyWorkoutRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\User;
use App\Models\Workout;
use App\Services\WorkoutCloneService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

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

    public function __construct(private WorkoutCloneService $cloneService) {}

    public function __invoke(CopyWorkoutRequest $request, Workout $workout): JsonResponse
    {
        $this->authorize('view', $workout);

        /** @var User $user */
        $user = $request->user();

        $copy = $this->cloneService->fromWorkout($workout, $user, $request->validated());

        $copy->load(self::WORKOUT_EAGER_LOAD);

        return (new WorkoutResource($copy))
            ->response()
            ->setStatusCode(201);
    }
}
