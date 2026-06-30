<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExerciseRequest;
use App\Http\Requests\Api\V1\UpdateExerciseRequest;
use App\Http\Resources\Api\V1\ExerciseResource;
use App\Models\Exercise;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    use AuthorizesRequests;

    private const EAGER_LOAD = ['resistance', 'timedHold', 'distance', 'interval', 'equipmentType'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Exercise::where(function ($q) use ($request) {
            $q->whereNull('user_id')
                ->orWhere('user_id', $request->user()->id);
        });

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('equipment_type_id')) {
            $query->where('equipment_type_id', $request->input('equipment_type_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $exercises = $query
            ->with(self::EAGER_LOAD)
            ->orderBy('name')
            ->get();

        $exerciseIds = $exercises->pluck('id');

        $workoutUsage = DB::table('exercise_workout')
            ->whereIn('exercise_id', $exerciseIds)
            ->selectRaw('exercise_id, count(*) as cnt')
            ->groupBy('exercise_id')
            ->pluck('cnt', 'exercise_id');

        $templateUsage = DB::table('template_exercises')
            ->whereIn('exercise_id', $exerciseIds)
            ->selectRaw('exercise_id, count(*) as cnt')
            ->groupBy('exercise_id')
            ->pluck('cnt', 'exercise_id');

        $exercisesWithEntries = DB::table('workout_entries')
            ->whereIn('exercise_id', $exerciseIds)
            ->distinct()
            ->pluck('exercise_id');

        $exercises->each(function (Exercise $exercise) use ($workoutUsage, $templateUsage, $exercisesWithEntries) {
            $exercise->setAttribute(
                'usage_count',
                ($workoutUsage[$exercise->id] ?? 0) + ($templateUsage[$exercise->id] ?? 0)
            );
            $exercise->setAttribute(
                'has_logged_data',
                $exercisesWithEntries->contains($exercise->id)
            );
        });

        return ExerciseResource::collection($exercises);
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = DB::transaction(function () use ($request) {
            $exercise = Exercise::create([
                'name' => $request->validated('name'),
                'type' => $request->validated('type'),
                'user_id' => $request->user()->id,
                'equipment_type_id' => $request->validated('equipment_type_id'),
                'notes' => $request->validated('notes'),
            ]);

            $typeAttributes = $request->validated('type_attributes') ?? [];

            $childRelation = match ($exercise->type) {
                ExerciseType::Resistance => 'resistance',
                ExerciseType::TimedHold => 'timedHold',
                ExerciseType::Distance => 'distance',
                ExerciseType::Interval => 'interval',
            };

            $exercise->$childRelation()->create($typeAttributes);

            return $exercise;
        });

        $exercise->load(self::EAGER_LOAD);

        return (new ExerciseResource($exercise))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Exercise $exercise): ExerciseResource
    {
        $this->authorize('view', $exercise);

        $exercise->load(self::EAGER_LOAD);

        $usageCount = DB::table('exercise_workout')->where('exercise_id', $exercise->id)->count()
            + DB::table('template_exercises')->where('exercise_id', $exercise->id)->count();
        $exercise->setAttribute('usage_count', $usageCount);

        return new ExerciseResource($exercise);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise): ExerciseResource
    {
        $this->authorize('update', $exercise);

        DB::transaction(function () use ($request, $exercise) {
            $exercise->update($request->safe()->only(['name', 'equipment_type_id', 'notes']));

            $validated = $request->validated();
            if (isset($validated['type_attributes'])) {
                $childRelation = match ($exercise->type) {
                    ExerciseType::Resistance => 'resistance',
                    ExerciseType::TimedHold => 'timedHold',
                    ExerciseType::Distance => 'distance',
                    ExerciseType::Interval => 'interval',
                };
                $exercise->$childRelation->update($validated['type_attributes']);
            }
        });

        $exercise->load(self::EAGER_LOAD);

        return new ExerciseResource($exercise);
    }

    public function destroy(Exercise $exercise): Response|JsonResponse
    {
        $this->authorize('delete', $exercise);

        $inUse = DB::table('exercise_workout')->where('exercise_id', $exercise->id)->exists()
            || DB::table('workout_entries')->where('exercise_id', $exercise->id)->exists()
            || DB::table('template_exercises')->where('exercise_id', $exercise->id)->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'Exercise is in use by workouts or templates.',
            ], 409);
        }

        $exercise->delete();

        return response()->noContent();
    }
}
