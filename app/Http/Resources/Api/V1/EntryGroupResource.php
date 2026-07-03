<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\EntryGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntryGroup */
class EntryGroupResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'planned_rounds' => $this->planned_rounds,
            'rest_between_exercises_seconds' => $this->rest_between_exercises_seconds,
            'rest_between_rounds_seconds' => $this->rest_between_rounds_seconds,
        ];
    }
}
