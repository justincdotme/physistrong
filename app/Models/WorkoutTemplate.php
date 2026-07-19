<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LocksForReorder;
use App\Models\Pivots\TemplateExercisePivot;
use Database\Factories\WorkoutTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutTemplate extends Model
{
    /** @use HasFactory<WorkoutTemplateFactory> */
    use HasFactory;

    use LocksForReorder;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'user_id',
        'notes',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Exercise, $this, TemplateExercisePivot, 'pivot'> */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'template_exercises', 'template_id', 'exercise_id')
            ->using(TemplateExercisePivot::class)
            ->withPivot('exercise_order', 'template_entry_group_id')
            ->orderByPivot('exercise_order');
    }

    /** @return HasMany<TemplateEntryGroup, $this> */
    public function groups(): HasMany
    {
        return $this->hasMany(TemplateEntryGroup::class, 'template_id');
    }
}
