<?php

namespace App\Rules;

use App\Core\User;
use App\Core\Exercise;
use Illuminate\Contracts\Validation\Rule;

class UniqueExercise implements Rule
{
    protected $user;
    protected $exercise;

    /**
     * Create a new rule instance.
     *
     * @param Exercise $exercise
     * @param User $user
     */
    public function __construct(Exercise $exercise, User $user)
    {
        $this->exercise = $exercise;
        $this->user = $user;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return !$this->exercise->where(['user_id' => $this->user->id, 'name' => $value])->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Only 1 unique exercise name per user.';
    }
}
