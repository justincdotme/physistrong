<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->first_name ?? 'there';

        return (new MailMessage())
            ->subject('Welcome to Physistrong')
            ->greeting("Hey {$name},")
            ->line('Your Physistrong account is ready. Start tracking your workouts today.')
            ->action('Open Physistrong', config('app.url'));
    }
}
