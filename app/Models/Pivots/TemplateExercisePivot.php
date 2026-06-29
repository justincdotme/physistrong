<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $exercise_order
 * @property int|null $template_entry_group_id
 */
class TemplateExercisePivot extends Pivot
{
    public $timestamps = false;

    protected $table = 'template_exercises';
}
