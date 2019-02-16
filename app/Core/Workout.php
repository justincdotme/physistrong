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
}
