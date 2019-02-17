<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;

class ExerciseType extends Model
{
    protected $guarded = [];
    protected $dates = ['created_at', 'updated_at'];
}
