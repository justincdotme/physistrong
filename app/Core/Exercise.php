<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $guarded = [];
    protected $dates = ['created_at', 'updated_at'];

    public function workouts()
    {
        return $this->belongsToMany(Workout::class)->withPivot('exercise_order');
    }
}
