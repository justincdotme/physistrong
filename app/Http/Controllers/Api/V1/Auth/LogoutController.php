<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthTokenCookie;
use App\Services\TokenBlacklist;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\AccessToken;

class LogoutController extends Controller
{
    public function __invoke(Request $request, TokenBlacklist $blacklist): Response
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof AccessToken) {
            // Read the raw attributes: the string @property type hides that actingAs tokens carry no id.
            $jti = $token->toArray()['oauth_access_token_id'] ?? null;
            $expiresAt = $token->expires_at;

            if (is_string($jti) && $expiresAt instanceof DateTimeInterface) {
                $blacklist->add($jti, $expiresAt);
            }

            $token->revoke();
        }

        return response()->noContent()->withCookie(AuthTokenCookie::expire());
    }
}
