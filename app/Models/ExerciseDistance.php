<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseDistance extends Model
{
    protected $table = 'exercise_distance';

    /** @var list<string> */
    protected $fillable = [
        'tracks_elevation',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tracks_elevation' => 'boolean',
        ];
    }

    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
