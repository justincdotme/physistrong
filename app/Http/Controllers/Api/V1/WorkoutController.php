<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWorkoutRequest;
use App\Http\Requests\Api\V1\UpdateWorkoutRequest;
use App\Http\Resources\Api\V1\WorkoutListResource;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\Workout;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorkoutController extends Controller
{
    use AuthorizesRequests;

    private const SHOW_EAGER_LOAD = [
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

    public function index(Request $request): AnonymousResourceCollection
    {
        $workouts = Workout::where('user_id', $request->user()->id)
            ->with('exercises')
            ->withCount([
                'entries',
                'entries as completed_entries_count' => function ($query) {
                    $query->where(function ($q) {
                        $q->whereHas('loadMetric', fn ($s) => $s->whereNotNull('actual_weight'))
                          ->orWhereHas('repMetric', fn ($s) => $s->whereNotNull('actual_reps'))
                          ->orWhereHas('durationMetric', fn ($s) => $s->whereNotNull('actual_duration_seconds'))
                          ->orWhereHas('distanceMetric', fn ($s) => $s->whereNotNull('actual_distance'))
                          ->orWhereHas('intervalHeader', fn ($s) => $s->where('completed_rounds', '>', 0));
                    });
                },
            ])
            ->latest('date')
            ->paginate(15);

        return WorkoutListResource::collection($workouts);
    }

    public function store(StoreWorkoutRequest $request): JsonResponse
    {
        $workout = Workout::create([
            'name' => $request->validated('name'),
            'user_id' => $request->user()->id,
            'date' => $request->validated('date'),
            'exhaustion' => $request->validated('exhaustion'),
            'soreness' => $request->validated('soreness'),
        ]);

        $workout->load(self::SHOW_EAGER_LOAD);

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Workout $workout): WorkoutResource
    {
        $this->authorize('view', $workout);

        $workout->load(self::SHOW_EAGER_LOAD);

        return new WorkoutResource($workout);
    }

    public function update(UpdateWorkoutRequest $request, Workout $workout): WorkoutResource
    {
        $this->authorize('update', $workout);

        $workout->update($request->validated());
        $workout->load(self::SHOW_EAGER_LOAD);

        return new WorkoutResource($workout);
    }

    public function destroy(Workout $workout): Response
    {
        $this->authorize('delete', $workout);

        $workout->delete();

        return response()->noContent();
    }
}
