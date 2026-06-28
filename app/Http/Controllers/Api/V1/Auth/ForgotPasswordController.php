<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $key = 'password-forgot:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Too many requests. Try again later.',
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If that email is registered, a reset link has been sent.',
        ]);
    }
}
