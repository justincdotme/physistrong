<?php

namespace Tests\Feature;

use App\Core\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Mail\PasswordResetRequest as PasswordResetRequestMail;

class PasswordResetTest extends TestCase
{
    use DatabaseMigrations;

    protected $passwordReset;
    protected $registeredUser;

    public function setUp()
    {
        parent::setUp();
        $this->registeredUser = factory(User::class)->create([
            'email' => 'test.user@physistrong.com',
            'password' => 'abc123'
        ]);
    }

    /**
     * @test
     */
    public function registered_user_can_reset_their_password()
    {
        Mail::fake();

        $this->response = $this->json("POST", route('password.request'), [
            'email' => $this->registeredUser->email
        ]);

        $this->response->assertStatus(200);
        $this->response->assertJsonStructure([
            'data' => [
                'type'
            ]
        ]);

        Mail::assertQueued(PasswordResetRequestMail::class);
    }

    /**
     * @test
     */
    public function email_address_must_belong_to_existing_user()
    {
        Mail::fake();

        $this->response = $this->json("POST", route('password.request'), [
            'email' => 'foo@bar.baz'
        ]);

        Mail::assertNotQueued(PasswordResetRequestMail::class);
        $this->assertFieldHasValidationError('email');
    }

    /**
     * @test
     */
    public function valid_token_is_required()
    {
        Mail::fake();

        $this->response = $this->json("POST", route('password.reset', [
            'token' => 'abc123'
        ]), [
            'email' => $this->registeredUser->email,
            'password' => 'testing123',
            'password_confirmation' => 'testing123'
        ]);

        $this->assertFieldHasValidationError('token');
        Mail::assertNotQueued(PasswordResetRequestMail::class);
    }
}
