<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\MetricDimension;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReorderWorkoutEntriesRequest;
use App\Http\Requests\Api\V1\StoreWorkoutEntryRequest;
use App\Http\Requests\Api\V1\UpdateWorkoutEntryRequest;
use App\Http\Resources\Api\V1\WorkoutEntryResource;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WorkoutEntryController extends Controller
{
    use AuthorizesRequests;

    /**
     * @param StoreWorkoutEntryRequest $request
     * @param Workout                  $workout
     *
     * @return JsonResponse
     */
    public function store(StoreWorkoutEntryRequest $request, Workout $workout): JsonResponse
    {
        $this->authorize('update', $workout);

        $entry = DB::transaction(function () use ($request, $workout) {
            $entry = $workout->entries()->create([
                'exercise_id'    => $request->validated('exercise_id'),
                'set_order'      => $request->validated('set_order'),
                'notes'          => $request->validated('notes'),
                'entry_group_id' => $request->validated('entry_group_id'),
                'group_round'    => $request->validated('group_round'),
            ]);

            $metrics = $request->validated('metrics') ?? [];

            if ($metrics) {
                $this->syncMetrics($entry, $metrics);
            }

            return $entry;
        });

        $entry->load(WorkoutEntry::metricRelations());

        return (new WorkoutEntryResource($entry))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @param UpdateWorkoutEntryRequest $request
     * @param Workout                   $workout
     * @param WorkoutEntry              $entry
     *
     * @return WorkoutEntryResource
     */
    public function update(UpdateWorkoutEntryRequest $request, Workout $workout, WorkoutEntry $entry): WorkoutEntryResource
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $entry): void {
            $entry->update($request->safe()->only(['set_order', 'notes', 'entry_group_id', 'group_round']));

            $metrics = $request->validated('metrics') ?? [];

            if ($metrics) {
                $this->syncMetrics($entry, $metrics);
            }
        });

        $entry->load(WorkoutEntry::metricRelations());

        return new WorkoutEntryResource($entry);
    }

    /**
     * @param Workout      $workout
     * @param WorkoutEntry $entry
     *
     * @return Response
     */
    public function destroy(Workout $workout, WorkoutEntry $entry): Response
    {
        $this->authorize('update', $workout);

        $entry->delete();

        return response()->noContent();
    }

    /**
     * @param ReorderWorkoutEntriesRequest $request
     * @param Workout                      $workout
     *
     * @return AnonymousResourceCollection
     */
    public function reorder(ReorderWorkoutEntriesRequest $request, Workout $workout): AnonymousResourceCollection
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $workout): void {
            Workout::whereKeyLocked($workout->id)->first();

            $ids = $request->validated('ids');

            foreach ($ids as $index => $id) {
                $workout->entries()->where('id', $id)->update(['set_order' => $index]);
            }
        });

        $entries = $workout->entries()
            ->with(WorkoutEntry::metricRelations())
            ->orderBy('set_order')
            ->get();

        return WorkoutEntryResource::collection($entries);
    }

    /**
     * @param WorkoutEntry         $entry
     * @param array<string, mixed> $metrics
     *
     * @return void
     */
    private function syncMetrics(WorkoutEntry $entry, array $metrics): void
    {
        foreach ($metrics as $key => $values) {
            $dimension = MetricDimension::tryFrom((string) $key);

            if ($dimension === null) {
                continue;
            }

            $relation = $dimension->relation();

            if ($dimension === MetricDimension::IntervalHeader) {
                $rounds = $values['rounds'] ?? [];
                unset($values['rounds']);
                $header = $entry->$relation()->updateOrCreate([], $values);
                $header->rounds()->delete();

                foreach ($rounds as $round) {
                    $header->rounds()->create($round);
                }
            } else {
                $entry->$relation()->updateOrCreate([], $values);
            }
        }
    }
}
