<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogCardioSetting extends Model
{
    /** @var string */
    protected $table = 'log_cardio_settings';

    /** @var list<string> */
    protected $fillable = [
        'resistance_level',
        'incline',
        'speed',
        'cadence',
    ];

    /** @return BelongsTo<WorkoutEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(WorkoutEntry::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'incline' => 'decimal:2',
            'speed'   => 'decimal:2',
        ];
    }
}
