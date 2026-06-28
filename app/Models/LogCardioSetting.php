<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogCardioSetting extends Model
{
    protected $table = 'log_cardio_settings';

    /** @var list<string> */
    protected $fillable = [
        'entry_id',
        'resistance_level',
        'incline',
        'speed',
        'cadence',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'incline' => 'decimal:2',
            'speed' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<WorkoutEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(WorkoutEntry::class);
    }
}
