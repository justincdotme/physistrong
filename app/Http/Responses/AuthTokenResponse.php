<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AuthTokenCookie;
use Illuminate\Http\JsonResponse;

class AuthTokenResponse
{
    public static function make(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('auth');

        return response()->json([
            'user' => new UserResource($user),
        ], $status)->withCookie(AuthTokenCookie::issue($token->accessToken, now()->addSeconds($token->expiresIn)));
    }
}
