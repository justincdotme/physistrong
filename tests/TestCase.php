<?php

namespace Tests;

use App\Core\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $response;

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

    /**
     * Helper method to test that a specific field has a validation error.
     *
     * @param $field
     */
    protected function assertFieldHasValidationError($field)
    {
        $this->response->assertStatus(422);
        $responseArray = collect($this->response->decodeResponseJson());
        $this->assertArrayHasKey('errors', $responseArray);

        foreach ($responseArray['errors'] as $error) {
            $this->assertEquals('422', $error['status']);
            $hasField = false;
            if (array_key_exists('source', $error) && array_key_exists('pointer', $error['source'])) {
                $pointerParts = explode('/', $error['source']['pointer']);
                $errorField = end($pointerParts);
                if ($field === $errorField) {
                    $hasField = true;
                    break;
                }
            }
        }
        if (!$hasField) {
            $this->fail('There is no errors array');
        }
    }

    /**
     * Render the contents of a mailable.
     * Allows us to run assertions against it's contents.
     *
     * @param $mailable
     * @return string
     * @throws \Throwable
     */
    protected function renderMailable($mailable)
    {
        $mailable->build();
        return view(
            $mailable->view,
            $mailable->buildViewData()
        )->render();
    }
}
