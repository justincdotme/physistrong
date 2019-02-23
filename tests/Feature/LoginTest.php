<?php

namespace Tests\Feature;

use App\Core\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginTest extends TestCase
{
    use DatabaseMigrations;

    protected $existingUser;

    public function setUp()
    {
        parent::setUp();
        $this->existingUser = factory(User::class)->create([
            'email' => 'test.user@physistrong.com',
            'password' => 'testing123'
        ]);
    }

    /**
     * @test
     */
    public function unauthenticated_user_can_login()
    {
        $this->response = $this->json("POST", route('user.login'), [
            'email' => 'test.user@physistrong.com',
            'password' => 'testing123'
        ]);


        $this->response->assertStatus(200);
        $responseArray = $this->response
            ->decodeResponseJson();
        $this->assertArrayHasKey('access_token', $responseArray['meta']);
        $this->assertArrayHasKey('token_type', $responseArray['meta']);
        $this->assertArrayHasKey('expires_in', $responseArray['meta']);
    }

    /**
     * @test
     */
    public function authenticated_users_can_not_login()
    {
        $this->response = $this->actingAs($this->existingUser)->json("POST", route('user.login'), [
            'email' => 'test.user@physistrong.com',
            'password' => 'testing123'
        ]);

        $this->response->assertStatus(409);
        $responseArray = $this->response
            ->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('409', $responseArray['errors']['status']);
        $this->assertEquals('The user is already authenticated.', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function unregistered_users_can_not_login()
    {
        $this->response = $this->json("POST", route('user.login'), [
            'email' => 'unknown.user@physistrong.com',
            'password' => 'foopass'
        ]);

        $this->response->assertStatus(401);
        $responseArray = $this->response
            ->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('401', $responseArray['errors']['status']);
        $this->assertEquals('Authorization failed.', $responseArray['errors']['detail']);
    }

    /**
     * @test
     */
    public function email_is_required()
    {
        $this->response = $this->json("POST", route('user.login'), [
            'password' => 'testing123'
        ]);

        $this->assertFieldHasValidationError('email');
    }

    /**
     * @test
     */
    public function password_is_required()
    {
        $this->response = $this->json("POST", route('user.login'), [
            'email' => 'test.user@physistrong.com'
        ]);

        $this->assertFieldHasValidationError('password');
    }
}
