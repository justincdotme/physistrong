<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExerciseWorkoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exercise_workouts', function (Blueprint $table) {
            $table->integer('workout_id')->unsigned()->index();
            $table->foreign('workout_id')->references('id')->on('workouts');
            $table->integer('exercise_id')->unsigned()->index();
            $table->foreign('exercise_id')->references('id')->on('exercises');
            $table->integer('exercise_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exercise_workouts');
    }
}
