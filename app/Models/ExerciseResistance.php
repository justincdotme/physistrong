<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseResistance extends Model
{
    protected $table = 'exercise_resistance';

    /** @var list<string> */
    protected $fillable = [
        'bodyweight_base',
        'allows_added_weight',
        'bilateral',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'bodyweight_base' => 'boolean',
            'allows_added_weight' => 'boolean',
            'bilateral' => 'boolean',
        ];
    }

    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
