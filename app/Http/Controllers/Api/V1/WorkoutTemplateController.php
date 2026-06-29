<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWorkoutTemplateRequest;
use App\Http\Requests\Api\V1\UpdateWorkoutTemplateRequest;
use App\Http\Resources\Api\V1\WorkoutTemplateListResource;
use App\Http\Resources\Api\V1\WorkoutTemplateResource;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorkoutTemplateController extends Controller
{
    use AuthorizesRequests;

    private const SHOW_EAGER_LOAD = ['exercises', 'groups'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $templates = WorkoutTemplate::where('user_id', $user->id)
            ->with('exercises')
            ->latest()
            ->get();

        return WorkoutTemplateListResource::collection($templates);
    }

    public function store(StoreWorkoutTemplateRequest $request): JsonResponse
    {
        $user = $request->user();
        $template = WorkoutTemplate::create([
            'name' => $request->validated('name'),
            'user_id' => $user->id,
            'notes' => $request->validated('notes'),
        ]);

        $template->load(self::SHOW_EAGER_LOAD);

        return (new WorkoutTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function show(WorkoutTemplate $template): WorkoutTemplateResource
    {
        $this->authorize('view', $template);

        $template->load(self::SHOW_EAGER_LOAD);

        return new WorkoutTemplateResource($template);
    }

    public function update(UpdateWorkoutTemplateRequest $request, WorkoutTemplate $template): WorkoutTemplateResource
    {
        $this->authorize('update', $template);

        $template->update($request->validated());
        $template->load(self::SHOW_EAGER_LOAD);

        return new WorkoutTemplateResource($template);
    }

    public function destroy(WorkoutTemplate $template): Response
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->noContent();
    }
}
