<?php

namespace App\Exceptions\Errors;

class JsonApi
{
    /**
     * Format errors per JSON API spec.
     *
     * @param $status - HTTP status code
     * @param $path - Relative URL
     * @param $detail - Error message detail
     *
     * @return array
     */
    public static function formatError($status, $path, $detail)
    {
        return [
            'errors' => [
                'status' => "{$status}",
                'source' => [
                    'pointer' => (substr($path, 0, 1) === '/') ? $path : "/{$path}"
                ],
                'detail' => $detail,
            ]
        ];
    }

    /**
     * Format a collection of validation per JSON API spec.
     *
     * @param $code
     * @param $errors
     * @return array
     */
    public static function formatValidationErrors($code, $errors)
    {
        $output = collect($errors)->flatMap(function ($item, $field) use ($code) {
            return collect($item)->map(function ($message, $intKey) use ($field, $code) {
                return [
                    'status' => "{$code}",
                    'source' => [
                        'pointer' => "/data/{$field}"
                    ],
                    'detail' => "{$message}"
                ];
            });
        });

        return [
            'errors' => $output->toArray()
        ];
    }
}
