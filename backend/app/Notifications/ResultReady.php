<?php

namespace App\Notifications;

use App\Models\TestAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent when an attempt is submitted (by the student or by the clock). */
class ResultReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private TestAttempt $attempt) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'result',
            'title' => 'Your result is ready',
            'body' => "{$this->attempt->test->title}. See your score, rank and solutions.",
            'href' => "/results/{$this->attempt->uuid}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your result: {$this->attempt->test->title}")
            ->greeting('Hi '.($notifiable->nickname ?: 'there').',')
            ->line("You scored {$this->attempt->score} in “{$this->attempt->test->title}”.")
            ->action('View result and solutions', config('lms.student_app_url').'/results/'.$this->attempt->uuid);
    }
}
