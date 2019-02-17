<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    protected $guarded = [];
    protected $with = ['exercise'];
    protected $dates = ['created_at', 'updated_at'];


    /**
     * @param Workout $workout
     * @param Exercise $exercise
     * @param $setOrder
     * @param $weight
     * @param $count
     * @return mixed
     */
    public static function forWorkout(Workout $workout, Exercise $exercise, $setOrder, $weight, $count)
    {
        return self::create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'weight' => $weight,
            'count' => $count,
            'set_order' => $setOrder
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * @param Exercise $exercise
     * @return mixed
     */
    public function scopeOfExercise($query, Exercise $exercise)
    {
        return $query->where('exercise_id', $exercise->id);
    }

    /**
     * @param $value
     * @return string
     */
    public function getWeightAttribute($value)
    {
        return ($value > 0 ? $value : 'Body');
    }
}
