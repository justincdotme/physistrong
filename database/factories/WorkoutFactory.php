<?php

use Faker\Generator as Faker;
use Illuminate\Support\Carbon;

$factory->define(App\Core\Workout::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => 'test workout',
        'date_scheduled' => Carbon::now()->toDateString(),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
