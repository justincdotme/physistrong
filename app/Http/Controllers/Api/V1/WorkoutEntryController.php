<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReorderRequest;
use App\Http\Requests\Api\V1\StoreWorkoutEntryRequest;
use App\Http\Requests\Api\V1\UpdateWorkoutEntryRequest;
use App\Http\Resources\Api\V1\WorkoutEntryResource;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WorkoutEntryController extends Controller
{
    use AuthorizesRequests;

    private const METRIC_EAGER_LOAD = [
        'loadMetric',
        'repMetric',
        'durationMetric',
        'distanceMetric',
        'cardioSetting',
        'intervalHeader.rounds',
        'intensityMetric',
    ];

    public function index(Request $request, Workout $workout): AnonymousResourceCollection
    {
        $this->authorize('view', $workout);

        $entries = $workout->entries()
            ->with(self::METRIC_EAGER_LOAD)
            ->orderBy('set_order')
            ->get();

        return WorkoutEntryResource::collection($entries);
    }

    public function store(StoreWorkoutEntryRequest $request, Workout $workout): JsonResponse
    {
        $this->authorize('update', $workout);

        $entry = DB::transaction(function () use ($request, $workout) {
            $entry = $workout->entries()->create([
                'exercise_id' => $request->validated('exercise_id'),
                'set_order' => $request->validated('set_order'),
                'notes' => $request->validated('notes'),
                'entry_group_id' => $request->validated('entry_group_id'),
                'group_round' => $request->validated('group_round'),
            ]);

            $metrics = $request->validated('metrics') ?? [];
            if ($metrics) {
                $this->syncMetrics($entry, $metrics);
            }

            return $entry;
        });

        $entry->load(self::METRIC_EAGER_LOAD);

        return (new WorkoutEntryResource($entry))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Workout $workout, WorkoutEntry $entry): WorkoutEntryResource
    {
        $this->authorize('view', $workout);

        $entry->load(self::METRIC_EAGER_LOAD);

        return new WorkoutEntryResource($entry);
    }

    public function update(UpdateWorkoutEntryRequest $request, Workout $workout, WorkoutEntry $entry): WorkoutEntryResource
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $entry) {
            $entry->update($request->safe()->only(['set_order', 'notes', 'entry_group_id', 'group_round']));

            $metrics = $request->validated('metrics') ?? [];
            if ($metrics) {
                $this->syncMetrics($entry, $metrics);
            }
        });

        $entry->load(self::METRIC_EAGER_LOAD);

        return new WorkoutEntryResource($entry);
    }

    public function destroy(Workout $workout, WorkoutEntry $entry): Response
    {
        $this->authorize('update', $workout);

        $entry->delete();

        return response()->noContent();
    }

    public function reorder(ReorderRequest $request, Workout $workout): AnonymousResourceCollection
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $workout) {
            $ids = $request->validated('ids');
            foreach ($ids as $index => $id) {
                $workout->entries()->where('id', $id)->update(['set_order' => $index]);
            }
        });

        $entries = $workout->entries()
            ->with(self::METRIC_EAGER_LOAD)
            ->orderBy('set_order')
            ->get();

        return WorkoutEntryResource::collection($entries);
    }

    /** @param array<string, mixed> $metrics */
    private function syncMetrics(WorkoutEntry $entry, array $metrics): void
    {
        $metricMap = [
            'load' => 'loadMetric',
            'reps' => 'repMetric',
            'duration' => 'durationMetric',
            'distance' => 'distanceMetric',
            'cardio_settings' => 'cardioSetting',
            'interval_header' => 'intervalHeader',
            'intensity' => 'intensityMetric',
        ];

        foreach ($metrics as $type => $data) {
            if (! isset($metricMap[$type])) {
                continue;
            }

            $relation = $metricMap[$type];

            if ($type === 'interval_header') {
                $rounds = $data['rounds'] ?? [];
                unset($data['rounds']);
                $header = $entry->$relation()->updateOrCreate([], $data);
                $header->rounds()->delete();
                foreach ($rounds as $round) {
                    $header->rounds()->create($round);
                }
            } else {
                $entry->$relation()->updateOrCreate([], $data);
            }
        }
    }
}
