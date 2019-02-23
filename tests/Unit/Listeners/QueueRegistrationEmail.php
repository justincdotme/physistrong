<?php

namespace Tests\Unit\Listeners;

use App\Core\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistrationConfirmation;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class QueueRegistrationEmail extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function it_queues_an_email_to_registered_user()
    {
        Mail::fake();

        factory(User::class)->create([
            'email' => 'test@user.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'abc123'
        ]);

        Mail::assertQueued(UserRegistrationConfirmation::class);
    }
}
