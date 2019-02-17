<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\Errors\JsonApi;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            if (! $request->expectsJson()) {
                return redirect('/');
            }

            return response()->json(
                JsonApi::formatError(409, $request->decodedPath(), 'The user is already authenticated.'),
                409
            );
        }

        return $next($request);
    }
}
