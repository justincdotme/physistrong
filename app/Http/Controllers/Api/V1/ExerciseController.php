<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExerciseRequest;
use App\Http\Requests\Api\V1\UpdateExerciseRequest;
use App\Http\Resources\Api\V1\ExerciseResource;
use App\Models\Exercise;
use App\Repositories\ExerciseRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ExerciseController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ExerciseRepository $exercises) {}

    /** @return list<string> */
    private function eagerLoad(): array
    {
        return [...ExerciseType::childRelations(), 'equipmentType'];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Exercise::visibleTo($request->user())->withUsage();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('equipment_type_id')) {
            $query->where('equipment_type_id', $request->input('equipment_type_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        return ExerciseResource::collection(
            $query->with($this->eagerLoad())->orderBy('name')->get()
        );
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = $this->exercises->create($request->user(), $request->validated());

        return (new ExerciseResource($exercise->load($this->eagerLoad())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Exercise $exercise): ExerciseResource
    {
        $this->authorize('view', $exercise);

        $exercise->load($this->eagerLoad())
            ->loadCount(['workouts', 'templates'])
            ->loadExists(['entries as has_logged_data']);

        return new ExerciseResource($exercise);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise): ExerciseResource
    {
        $this->authorize('update', $exercise);

        $this->exercises->update($exercise, $request->validated());

        return new ExerciseResource($exercise->load($this->eagerLoad()));
    }

    public function destroy(Exercise $exercise): Response|JsonResponse
    {
        $this->authorize('delete', $exercise);

        if ($exercise->isInUse()) {
            return response()->json([
                'message' => 'Exercise is in use by workouts or templates.',
            ], 409);
        }

        $exercise->delete();

        return response()->noContent();
    }
}
