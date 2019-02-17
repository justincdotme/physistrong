<?php

use App\Core\Exercise;
use Faker\Generator as Faker;
use Illuminate\Support\Carbon;

$factory->define(Exercise::class, function (Faker $faker) {
    return [
        'name' => "{$faker->word} {$faker->word}",
        'user_id' => 1,
        'exercise_type_id' => 1,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
