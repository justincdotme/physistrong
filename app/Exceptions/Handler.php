<?php

namespace App\Exceptions;

use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

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
     * @param  \Throwable $exception
     * @return void
     * @throws Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
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
                case ($exception instanceof UndeletableException):
                    $code = 409;
                    $detail = 'The resource could not be deleted due to dependency conflict.';
                    break;
                case ($exception instanceof ValidationException):
                    return response()->jsonApiError($exception->validator->errors()->getMessages(), 422);
                    break;
                case ($exception instanceof ModelNotFoundException):
                    $code = 404;
                    $detail = 'Not found';
                    break;
                default:
                    $code = 520;
                    $detail = 'Something bad happened, we\'re looking into it';
            }
            return response()->jsonApiError($detail, $code);
        }
        return parent::render($request, $exception);
    }
}
