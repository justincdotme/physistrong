<?php

namespace Tests\Unit\Mail;

use Tests\TestCase;
use Illuminate\Support\Str;
use App\Mail\PasswordResetRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PasswordResetRequestEmailTest extends TestCase
{
    use DatabaseMigrations;

    protected $passwordReset;

    public function setUp()
    {
        parent::setUp();
    }
    /**
     * @test
     */
    public function email_contains_correct_content()
    {
        $token = Str::random(60);
        $resetEndpoint = route('password.request', [
            'token' => $token
        ]);

        $rendered = $this->renderMailable(
            new PasswordResetRequest($token)
        );

        $this->assertContains($resetEndpoint, $rendered);
        $this->assertContains('This link will expire in 60 minutes', $rendered);
    }

    /**
     * @test
     */
    public function email_has_subject()
    {
        $email = new PasswordResetRequest(Str::random(60));

        $this->assertEquals('Password Reset Request', $email->build()->subject);
    }
}
