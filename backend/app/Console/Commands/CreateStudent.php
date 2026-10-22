<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\User;
use App\Services\Courses\EnrollmentService;
use Illuminate\Console\Command;

/** Creates a student with a completed profile, so a tester can log in straight away (phone + OTP). */
class CreateStudent extends Command
{
    protected $signature = 'lms:create-student {phone : 10-digit mobile number}
        {--name=Test Student : Full name}
        {--email= : Email address (defaults to <phone>@example.com)}
        {--all-courses : Also assign every existing course}';

    protected $description = 'Create a student (or update the existing one with this phone) with a completed profile';

    public function handle(EnrollmentService $enrollments): int
    {
        $phone = preg_replace('/\D+/', '', $this->argument('phone'));
        if (strlen($phone) < 10) {
            $this->error('Enter a 10-digit mobile number.');

            return self::FAILURE;
        }
        $phone = substr($phone, -10);
        $name = (string) $this->option('name');

        $student = User::updateOrCreate(['phone' => $phone], [
            'name' => $name,
            'nickname' => explode(' ', trim($name))[0],
            'email' => $this->option('email') ?: "{$phone}@example.com",
            'target_exam' => 'IMU-CET',
            'attempt_year' => now()->year + 1,
            'profile_completed_at' => now(),
            'is_active' => true,
        ]);
        $this->info(($student->wasRecentlyCreated ? 'Created' : 'Updated')." student {$name} ({$phone}).");

        if ($this->option('all-courses')) {
            $count = Course::all()->filter(fn (Course $course) => $enrollments->assign($student, $course))->count();
            $this->info("Assigned {$count} course(s).");
        }

        return self::SUCCESS;
    }
}
