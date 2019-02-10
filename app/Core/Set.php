<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    protected $guarded = [];
    protected $with = ['exercise'];

    /**
     * @param Workout $workout
     * @param Exercise $exercise
     * @param $weight
     * @param $count
     * @return mixed
     */
    public static function forWorkout(Workout $workout, Exercise $exercise, $weight, $count)
    {
        return self::create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'weight' => $weight,
            'count' => $count,
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
}
