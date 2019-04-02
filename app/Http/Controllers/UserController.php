<?php

namespace App\Http\Controllers;

use App\Core\User;
use Tymon\JWTAuth\Facades\JWTAuth;
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

        $user = User::create(
            request()->only([
                'email',
                'first_name',
                'last_name',
                'password'
            ])
        );

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
            return response()->jsonApiError("Authorization failed.", 401);
        }

        return response()
            ->json(
                (new UserResource(
                    User::where(request()->only(['email']))->first()
                ))->additional([
                    'meta' => [
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        'expires_in' => (auth()->factory()->getTTL() * 60)
                    ]
                ])->response()->getData(true)
            )->cookie(
                'authentication',
                $token,
                (60 * 24 * 7),
                '/',
                ('127.0.0.1' === config('app.app_base_url') ? null : '.' . config('app.app_base_url'))
            );
    }

    /**
     * @return UserResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function fromToken()
    {
        $user = JWTAuth::setToken($_COOKIE['authentication'])->toUser();

        $this->authorize('view', [User::class, $user]);

        return new UserResource($user);
    }

    /**
     * Log the user out.
     * Difficult to invalidate the token, but this removes the token from a device by unsetting the cookie.
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function logout()
    {
        return response(null, 200)
            ->withCookie(
                'authentication',
                '',
                -1,
                '/',
                (
                '127.0.0.1' === config('app.app_base_url') ? null : '.' . config('app.app_base_url')
                )
            );
    }
}
