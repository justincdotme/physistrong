<?php

namespace Tests\Unit\Mail;

use Tests\TestCase;
use App\Mail\PasswordResetRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PasswordResetRequestEmailTest extends TestCase
{
    use DatabaseMigrations;

    protected $passwordReset;

    /**
     * @test
     */
    public function email_contains_correct_content()
    {
        $token = 12345;
        $resetEndpoint = route('password.request', [
            'token' => $token
        ]);

        $rendered = $this->renderMailable(
            new PasswordResetRequest($token)
        );

        $this->assertStringContainsString($resetEndpoint, $rendered);
        $this->assertStringContainsString('This link will expire in 60 minutes', $rendered);
    }

    /**
     * @test
     */
    public function email_has_subject()
    {
        $email = new PasswordResetRequest(12345);

        $this->assertEquals('Password Reset Request', $email->build()->subject);
    }
}
