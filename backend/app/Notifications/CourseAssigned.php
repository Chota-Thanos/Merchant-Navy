<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** An admin assigned a course. In-app bell + email (when the student has an email). */
class CourseAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Course $course) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    /** Shape matches `AppNotification` in student-app/src/types/index.ts */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_assigned',
            'title' => 'New course assigned',
            'body' => "You now have access to {$this->course->title}.",
            'href' => "/my-courses/{$this->course->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You now have access to {$this->course->title}")
            ->greeting('Hi '.($notifiable->nickname ?: 'there').',')
            ->line("Your course “{$this->course->title}” is ready on your dashboard.")
            ->action('Start learning', config('lms.student_app_url').'/my-courses/'.$this->course->id);
    }
}
