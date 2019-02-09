<?php

namespace App\Exceptions;

use App\Exceptions\Errors\JsonApi;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception $exception
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($request->ajax() || $request->wantsJson()) {
            switch ($exception) {
                case ($exception instanceof TokenInvalidException):
                    $code = 401;
                    $detail = 'Invalid token';
                    break;
                case ($exception instanceof TokenExpiredException):
                    $code = 401;
                    $detail = 'Expired token';
                    break;
                case ($exception instanceof JWTException):
                    $code = 401;
                    $detail = 'Missing token';
                    break;
                case ($exception instanceof AuthorizationException):
                    $code = 403;
                    $detail = 'This action is unauthorized';
                    break;
            }
            return response()->json(
                JsonApi::formatError($code, $request->decodedPath(), $detail),
                $code
            );
        }
        return parent::render($request, $exception);
    }
}
