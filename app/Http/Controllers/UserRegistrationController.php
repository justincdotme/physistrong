<?php

namespace App\Http\Controllers;

use App\Core\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistrationConfirmation;
use App\Http\Resources\User as UserResource;

class UserRegistrationController extends Controller
{
    /**
     * Register a new user
     *
     * @return UserResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store()
    {
        $this->validate(
            request(),
            [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:6', 'confirmed']
            ]
        );

        $user = User::create([
            'email' => request('email'),
            'first_name' => request('first_name'),
            'last_name' => request('last_name'),
            'password' => bcrypt(request('password'))
        ]);

        Mail::to($user->email)->send(new UserRegistrationConfirmation($user));

        return new UserResource($user);
    }
}
