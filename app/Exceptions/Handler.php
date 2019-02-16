<?php

namespace App\Exceptions;

use Exception;
use App\Exceptions\Errors\JsonApi;
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
                case ($exception instanceof ValidationException):
                    return response()->json(
                        JsonApi::formatValidationErrors(
                            422,
                            $exception->validator->errors()->getMessages()
                        ),
                        422
                    );
                    break;
                case ($exception instanceof ModelNotFoundException):
                    $code = 404;
                    $detail = 'Not found';
                    break;
                default:
                    $code = 520;
                    $detail = 'Something bad happened, we\'re looking into it';
            }
            return response()->json(
                JsonApi::formatError($code, $request->decodedPath(), $detail),
                $code
            );
        }
        return parent::render($request, $exception);
    }
}
