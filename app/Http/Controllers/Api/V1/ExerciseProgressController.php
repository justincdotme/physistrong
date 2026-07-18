<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExerciseProgressRequest;
use App\Models\Exercise;
use App\Models\User;
use App\Services\ExerciseProgressService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseProgressController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ExerciseProgressService $progressService) {}

    public function progress(ExerciseProgressRequest $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        $range = $request->validated('range', 'all');

        /** @var User $user */
        $user = $request->user();

        $data = $this->progressService->getProgressData($exercise, $user, $range);

        // SPA charts need floats like 1.0 to stay as floats; JsonResource re-encodes and drops
        // the zero fraction. ExerciseProgressTest pins this with ~9 float assertions.
        return response()->json(['data' => $data], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function records(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        /** @var User $user */
        $user = $request->user();

        $data = $this->progressService->getPersonalRecords($exercise, $user);

        // SPA charts need floats like 1.0 to stay as floats; JsonResource re-encodes and drops
        // the zero fraction. ExerciseProgressTest pins this with ~9 float assertions.
        return response()->json(['data' => $data], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
