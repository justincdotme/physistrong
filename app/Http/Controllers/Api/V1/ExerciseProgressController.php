<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Services\ExerciseProgressService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class ExerciseProgressController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ExerciseProgressService $progressService) {}

    public function progress(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        $validated = $request->validate([
            'range' => ['sometimes', 'in:1m,3m,6m,1y,all'],
        ]);

        $range = $validated['range'] ?? 'all';

        /** @var User $user */
        $user = $request->user();

        $data = $this->progressService->getProgressData($exercise, $user, $range);

        return response()->json(['data' => $data], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function records(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        /** @var User $user */
        $user = $request->user();

        $data = $this->progressService->getPersonalRecords($exercise, $user);

        return response()->json(['data' => $data], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
