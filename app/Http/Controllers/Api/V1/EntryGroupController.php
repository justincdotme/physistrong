<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignGroupEntriesRequest;
use App\Http\Requests\Api\V1\DestroyEntryGroupRequest;
use App\Http\Requests\Api\V1\StoreEntryGroupRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\EntryGroup;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use App\Services\WorkoutCloneService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class EntryGroupController extends Controller
{
    use AuthorizesRequests;

    /**
     * @param StoreEntryGroupRequest $request
     * @param Workout                $workout
     *
     * @return JsonResponse
     */
    public function store(StoreEntryGroupRequest $request, Workout $workout): JsonResponse
    {
        $this->authorize('update', $workout);

        $workout->groups()->create($request->validated());

        $workout->load(Workout::detailRelations());

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @param DestroyEntryGroupRequest $request
     * @param Workout                  $workout
     * @param EntryGroup               $group
     *
     * @return Response
     */
    public function destroy(DestroyEntryGroupRequest $request, Workout $workout, EntryGroup $group): Response
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $workout, $group): void {
            if ($request->boolean('delete_entries')) {
                $exerciseIds = $group->entries()->pluck('exercise_id')->unique();
                $group->entries()->delete();
                $group->delete();

                foreach ($exerciseIds as $exerciseId) {
                    $hasRemainingEntries = $workout->entries()
                        ->where('exercise_id', $exerciseId)
                        ->exists();

                    if (! $hasRemainingEntries) {
                        $workout->exercises()->detach($exerciseId);
                    }
                }
            } else {
                // Clear group_round before delete; FK ON DELETE SET NULL handles entry_group_id
                $group->entries()->update(['group_round' => null]);
                $group->delete();
            }
        });

        return response()->noContent();
    }

    /**
     * @param AssignGroupEntriesRequest $request
     * @param Workout                   $workout
     * @param EntryGroup                $group
     * @param WorkoutCloneService       $cloneService
     *
     * @return WorkoutResource
     */
    public function assignEntries(
        AssignGroupEntriesRequest $request,
        Workout $workout,
        EntryGroup $group,
        WorkoutCloneService $cloneService,
    ): WorkoutResource {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $workout, $group, $cloneService): void {
            foreach ($request->validated('entries') as $assignment) {
                $workout->entries()
                    ->where('id', $assignment['entry_id'])
                    ->update([
                        'entry_group_id' => $group->id,
                        'group_round'    => $assignment['group_round'],
                    ]);
            }

            $this->expandRounds($workout, $group, $cloneService);
        });

        $workout->load(Workout::detailRelations());

        return new WorkoutResource($workout);
    }

    /**
     * Replicates round-1 entries into empty higher rounds so multi-round
     * groups created via the SPA's assign flow match the template-clone path.
     *
     * @param Workout             $workout
     * @param EntryGroup          $group
     * @param WorkoutCloneService $cloneService
     *
     * @return void
     */
    private function expandRounds(Workout $workout, EntryGroup $group, WorkoutCloneService $cloneService): void
    {
        if ($group->planned_rounds <= 1) {
            return;
        }

        $round1Entries = $group->entries()
            ->where('group_round', 1)
            ->orderBy('set_order')
            ->get();

        if ($round1Entries->isEmpty()) {
            return;
        }

        // Intensity is never cloned, so loading it would be wasted queries.
        $round1Entries->load(array_filter(
            WorkoutEntry::metricRelations(),
            fn (string $r) => $r !== 'intensityMetric',
        ));

        $setOrder = (int) $workout->entries()->max('set_order') + 1;

        for ($round = 2; $round <= $group->planned_rounds; $round++) {
            $roundHasEntries = $group->entries()
                ->where('group_round', $round)
                ->exists();

            if ($roundHasEntries) {
                continue;
            }

            foreach ($round1Entries as $sourceEntry) {
                $clone = $workout->entries()->create([
                    'exercise_id'    => $sourceEntry->exercise_id,
                    'entry_group_id' => $group->id,
                    'group_round'    => $round,
                    'set_order'      => $setOrder++,
                ]);

                $cloneService->cloneMetrics($sourceEntry, $clone);
            }
        }
    }
}
