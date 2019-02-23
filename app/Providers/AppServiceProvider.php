<?php

namespace App\Providers;

use App\Exceptions\Errors\JsonApi;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Response::macro('jsonApiError', function ($data, $code) {
            if (is_array($data)) {
                $formattedError = JsonApi::formatValidationErrors(
                    $code,
                    $data
                );
            } else {
                $formattedError = JsonApi::formatError(
                    $code,
                    request()->path(),
                    $data
                );
            }

            return response()
                ->json($formattedError, $code)
                ->withHeaders(['Content-Type' => 'application/vnd.api+json']);
        });
    }
}
