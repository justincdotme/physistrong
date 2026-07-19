<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuthTokenCookieService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateViaTokenCookie
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie(AuthTokenCookieService::NAME);

        // The header always wins so bearer clients (curl, the future mobile
        // app) behave exactly as before the cookie existed.
        if (! $request->headers->has('Authorization') && is_string($token) && $token !== '') {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
