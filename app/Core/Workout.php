<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    public $guarded = [];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
