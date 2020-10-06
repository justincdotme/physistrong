<?php

namespace Tests\Feature\API;

use App\Core\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistrationConfirmation;
use App\Http\Resources\User as UserResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CreateUserTest extends TestCase
{
    use DatabaseMigrations;

    protected $existingUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->existingUser = factory(User::class)->create();
    }

    /**
     * @test
     */
    public function unauthenticated_user_can_register()
    {
        Mail::fake();
        $this->response = $this->json("POST", route('user.store'), [
            'first_name' => 'Foo',
            'last_name' => 'McBar',
            'email' => 'test.user@physistrong.com',
            'password' => 'testing123',
            'password_confirmation' => 'testing123'
        ]);

        $user = User::where('email', 'test.user@physistrong.com')->firstOrFail();
        $resource = new UserResource($user);

        $this->response->assertStatus(201);
        $this->response->assertResource($resource);
        $this->assertEquals('test.user@physistrong.com', $user->email);
        Mail::assertQueued(UserRegistrationConfirmation::class);
    }

    /**
     * @test
     */
    public function authenticated_users_can_not_register()
    {
        Mail::fake();
        $this->response = $this->actingAs($this->existingUser)->json("POST", route('user.store'), [
            'first_name' => 'Foo',
            'last_name' => 'Foo',
            'email' => 'test.user@physistrong.com',
            'password' => 'testing123',
            'password_confirmation' => 'testing123'
        ]);

        $this->response->assertStatus(409);
        $this->assertDatabaseMissing('users', [
            'email' => 'test.user@physistrong.com'
        ]);
        $responseArray = $this->response->decodeResponseJson();
        $this->assertArrayHasKey('errors', $responseArray);
        $this->assertEquals('409', $responseArray['errors']['status']);
        $this->assertEquals('The user is already authenticated.', $responseArray['errors']['detail']);
        Mail::assertNotQueued(UserRegistrationConfirmation::class);
    }

    /**
     * @test
     */
    public function email_is_required()
    {
        $this->response = $this->json("POST", route('user.store'), [
            'first_name' => 'Foo',
            'last_name' => 'Foo',
            'password' => 'testing123',
            'password_confirmation' => 'testing123'
        ]);

        $this->assertFieldHasValidationError('email');
    }

    /**
     * @test
     */
    public function email_must_be_unique()
    {
        $this->response = $this->json("POST", route('user.store'), [
            'first_name' => 'Foo',
            'last_name' => 'Foo',
            'email' => $this->existingUser->email,
            'password' => 'testing123',
            'password_confirmation' => 'testing123'
        ]);

        $this->assertFieldHasValidationError('email');
    }

    /**
     * @test
     */
    public function password_is_required()
    {
        $this->response = $this->json("POST", route('user.store'), [
            'first_name' => 'Foo',
            'last_name' => 'Foo',
            'email' => 'test.user@physistrong.com',
            'password_confirmation' => 'testing123'
        ]);

        $this->assertFieldHasValidationError('password');
    }

    /**
     * @test
     */
    public function password_and_password_confirmation_must_match()
    {
        $this->response = $this->json("POST", route('user.store'), [
            'first_name' => 'Foo',
            'last_name' => 'Foo',
            'email' => 'test.user@physistrong.com',
            'password' => 'testing123',
            'password_confirmation' => 'foo'
        ]);

        $this->assertFieldHasValidationError('password');
    }

    /**
     * @test
     */
    public function password_must_be_at_least_6_char()
    {
        $this->response = $this->json("POST", route('user.store'), [
            'first_name' => 'Foo',
            'last_name' => 'Foo',
            'email' => 'test.user@physistrong.com',
            'password' => 'abc',
            'password_confirmation' => 'abc'
        ]);

        $this->assertFieldHasValidationError('password');
    }

    /**
     * @test
     */
    public function password_confirmation_is_required()
    {
        $this->response = $this->json("POST", route('user.store'), [
            'first_name' => 'Foo',
            'last_name' => 'Foo',
            'email' => 'test.user@physistrong.com',
            'password' => 'testing123'
        ]);

        $this->assertFieldHasValidationError('password');
    }
}
