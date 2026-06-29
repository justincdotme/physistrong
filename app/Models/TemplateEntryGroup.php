<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateEntryGroup extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'template_id',
        'name',
        'planned_rounds',
        'rest_between_exercises_seconds',
        'rest_between_rounds_seconds',
    ];

    /** @return BelongsTo<WorkoutTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkoutTemplate::class, 'template_id');
    }
}
