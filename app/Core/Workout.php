<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\UndeletableException;

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sets()
    {
        return $this->hasMany(Set::class)
            ->orderBy('exercise_id', 'ASC')
            ->orderBy('set_order', 'ASC');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function exercises()
    {
        return $this->belongsToMany(Exercise::class)->withPivot('exercise_order')->orderBy('exercise_order');
    }

    /**
     * Add an exercise to a workout.
     *
     * @param Exercise $exercise
     * @param $order
     * @return Model
     */
    public function addExercise(Exercise $exercise, $order)
    {
        return $this->exercises()->save($exercise, ['exercise_order' => $order]);
    }

    /**
     * Remove an exercise from a workout.
     *
     * @param Exercise $exercise
     * @throws UndeletableException
     */
    public function removeExercise(Exercise $exercise)
    {
        if ($this->sets()->ofExercise($exercise)->count()) {
            throw new UndeletableException();
        }

        $this->exercises()->detach($exercise->id);
    }
}
