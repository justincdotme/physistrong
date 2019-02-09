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
                    //'pointer' => "/{$path}"
                    'pointer' => (substr($path, 0, 1) === '/') ? $path : "/{$path}"
                ],
                'detail' => $detail,
            ]
        ];
    }
}
