<?php

namespace Tests;

use App\Core\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @var User
     */
    protected $user;
    /**
     * Set the currently logged in user for the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string|null                                $driver
     * @return $this
     */
    public function actingAs(UserContract $user, $driver = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string $method
     * @param  string $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if ($this->user) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . JWTAuth::fromUser($this->user);
        }

        $server['HTTP_ACCEPT'] = 'application/json';

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}