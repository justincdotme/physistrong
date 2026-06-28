<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Token;

class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof Token) {
            $token->revoke();
        }

        return response()->noContent();
    }
}
