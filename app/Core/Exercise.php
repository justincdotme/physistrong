<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $guarded = [];
    protected $dates = ['created_at', 'updated_at'];
    protected $with = ['type'];
    protected $appends = ['measurement'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function workouts()
    {
        return $this->belongsToMany(Workout::class)->withPivot('exercise_order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(ExerciseType::class, 'exercise_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return string
     */
    public function getMeasurementAttribute()
    {
        return (1 == $this->exercise_type_id ? 'reps' : 'seconds');
    }
}
