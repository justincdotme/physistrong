<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEquipmentTypeRequest;
use App\Http\Resources\Api\V1\EquipmentTypeResource;
use App\Models\EquipmentType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EquipmentTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $types = EquipmentType::withCount('exercises as usage_count')
            ->visibleTo($request->user())
            ->orderBy('name')
            ->get();

        return EquipmentTypeResource::collection($types);
    }

    public function store(StoreEquipmentTypeRequest $request): JsonResponse
    {
        $type = EquipmentType::create([
            'name' => $request->validated('name'),
            'user_id' => $request->user()->id,
            'is_system' => false,
        ]);

        return (new EquipmentTypeResource($type))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(EquipmentType $equipmentType): Response|JsonResponse
    {
        $this->authorize('delete', $equipmentType);

        if ($equipmentType->exercises()->exists()) {
            return response()->json([
                'message' => 'Equipment type is in use by exercises.',
            ], 409);
        }

        $equipmentType->delete();

        return response()->noContent();
    }
}
