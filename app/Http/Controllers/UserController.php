<?php

namespace App\Http\Controllers;

use App\Core\User;
use App\Exceptions\Errors\JsonApi;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistrationConfirmation;
use App\Http\Resources\User as UserResource;

class UserController extends Controller
{

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return UserResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(User $user)
    {
        $this->authorize('view', [User::class, $user]);
        return new UserResource($user);
    }

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

    /**
     * Log a user in.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login()
    {
        $this->validate(
            request(),
            [
                'email' => 'required',
                'password' => 'required'
            ]
        );

        if (! $token = JWTAuth::attempt(request(['email', 'password']))) {
            return response()->json(
                JsonApi::formatError(401, request()->path(), "Authorization failed."),
                401
            );
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (auth()->factory()->getTTL() * 60)
        ]);
    }
}
