<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TokenBlacklistService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Symfony\Component\HttpFoundation\Response;

class RejectBlacklistedTokens
{
    public function __construct(private TokenBlacklistService $blacklist) {}

    /**
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        $jti = $token instanceof AccessToken ? $token->oauth_access_token_id : null;

        if (is_string($jti) && $this->blacklist->has($jti)) {
            throw new AuthenticationException();
        }

        return $next($request);
    }
}
