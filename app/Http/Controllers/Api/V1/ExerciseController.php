<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexExerciseRequest;
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

    /** @param ExerciseRepository $exercises */
    public function __construct(private ExerciseRepository $exercises) {}

    /**
     * @param IndexExerciseRequest $request
     *
     * @return AnonymousResourceCollection
     */
    public function index(IndexExerciseRequest $request): AnonymousResourceCollection
    {
        $query     = Exercise::visibleTo($request->user())->withUsage($request->user());
        $validated = $request->validated();

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['equipment_type_id'])) {
            $query->where('equipment_type_id', $validated['equipment_type_id']);
        }

        if (isset($validated['search'])) {
            $search = addcslashes($validated['search'], '%_\\');
            $query->whereRaw('name LIKE ? ESCAPE ?', ['%' . $search . '%', '\\']);
        }

        return ExerciseResource::collection(
            $query->with($this->eagerLoad())->orderBy('name')->get(),
        );
    }

    /**
     * @param StoreExerciseRequest $request
     *
     * @return JsonResponse
     */
    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = $this->exercises->create($request->user(), $request->validated());

        return (new ExerciseResource($exercise->load($this->eagerLoad())))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @param Request  $request
     * @param Exercise $exercise
     *
     * @return ExerciseResource
     */
    public function show(Request $request, Exercise $exercise): ExerciseResource
    {
        $this->authorize('view', $exercise);

        $exercise->load($this->eagerLoad())->loadUsage($request->user());

        return new ExerciseResource($exercise);
    }

    /**
     * @param UpdateExerciseRequest $request
     * @param Exercise              $exercise
     *
     * @return ExerciseResource
     */
    public function update(UpdateExerciseRequest $request, Exercise $exercise): ExerciseResource
    {
        $this->authorize('update', $exercise);

        $this->exercises->update($exercise, $request->validated());

        $exercise->load($this->eagerLoad())->loadUsage($request->user());

        return new ExerciseResource($exercise);
    }

    /**
     * @param Exercise $exercise
     *
     * @return Response|JsonResponse
     */
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

    /** @return list<string> */
    private function eagerLoad(): array
    {
        return [...ExerciseType::childRelations(), 'equipmentType'];
    }
}
