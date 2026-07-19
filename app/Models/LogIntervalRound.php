<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogIntervalRound extends Model
{
    /** @var string */
    protected $table = 'log_interval_rounds';

    /** @var list<string> */
    protected $fillable = [
        'round_number',
        'actual_work_seconds',
        'actual_rest_seconds',
        'heart_rate_avg',
        'heart_rate_peak',
    ];

    /** @return BelongsTo<LogIntervalHeader, $this> */
    public function header(): BelongsTo
    {
        return $this->belongsTo(LogIntervalHeader::class, 'interval_header_id');
    }
}
