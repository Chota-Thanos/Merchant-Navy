<?php

namespace App\Notifications;

use App\Models\ChapterContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Reminder sent shortly before a live class (see App\Console\Commands\SendLiveClassReminders). */
class LiveClassStartingSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private ChapterContent $content) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'live_class',
            'title' => 'Live class starting soon',
            'body' => "{$this->content->title} starts at {$this->content->contentable->starts_at->format('h:i A')}.",
            'href' => "/learn/{$this->content->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Starting soon: {$this->content->title}")
            ->greeting('Hi '.($notifiable->nickname ?: 'there').',')
            ->line("“{$this->content->title}” starts at {$this->content->contentable->starts_at->format('d M, h:i A')}.")
            ->action('Open the class', config('lms.student_app_url').'/learn/'.$this->content->id);
    }
}
