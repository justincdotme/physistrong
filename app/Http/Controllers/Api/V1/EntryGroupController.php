<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignGroupEntriesRequest;
use App\Http\Requests\Api\V1\StoreEntryGroupRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\EntryGroup;
use App\Models\Workout;
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
     * @param Workout    $workout
     * @param EntryGroup $group
     *
     * @return Response
     */
    public function destroy(Workout $workout, EntryGroup $group): Response
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($group): void {
            // Clear group_round before delete; FK ON DELETE SET NULL handles entry_group_id
            $group->entries()->update(['group_round' => null]);
            $group->delete();
        });

        return response()->noContent();
    }

    /**
     * @param AssignGroupEntriesRequest $request
     * @param Workout                   $workout
     * @param EntryGroup                $group
     *
     * @return WorkoutResource
     */
    public function assignEntries(AssignGroupEntriesRequest $request, Workout $workout, EntryGroup $group): WorkoutResource
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($request, $workout, $group): void {
            foreach ($request->validated('entries') as $assignment) {
                $workout->entries()
                    ->where('id', $assignment['entry_id'])
                    ->update([
                        'entry_group_id' => $group->id,
                        'group_round'    => $assignment['group_round'],
                    ]);
            }
        });

        $workout->load(Workout::detailRelations());

        return new WorkoutResource($workout);
    }
}
