<?php

namespace App\Console\Commands;

use App\Models\ChapterContent;
use App\Models\LiveClass;
use App\Models\User;
use App\Notifications\LiveClassStartingSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/** Notifies the students of a course shortly before one of its live classes starts. Scheduled every minute. */
class SendLiveClassReminders extends Command
{
    protected $signature = 'live-classes:remind';

    protected $description = 'Notify students about live classes starting within the next 15 minutes';

    /** Minutes before the class that the reminder goes out. */
    private const LEAD_MINUTES = 15;

    public function handle(): int
    {
        $due = LiveClass::whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [now(), now()->addMinutes(self::LEAD_MINUTES)])->get();

        foreach ($due as $live) {
            $content = ChapterContent::where('contentable_type', $live->getMorphClass())->where('contentable_id', $live->id)
                ->with('chapter.subject')->first();

            if ($content) {
                $students = User::where('is_active', true)
                    ->whereHas('courses', fn ($q) => $q->where('courses.id', $content->chapter->subject->course_id))->get();
                Notification::send($students, new LiveClassStartingSoon($content->setRelation('contentable', $live)));
            }

            $live->update(['reminder_sent_at' => now()]);
        }

        $this->info("Reminders sent for {$due->count()} live class(es).");

        return self::SUCCESS;
    }
}
