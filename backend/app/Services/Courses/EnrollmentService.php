<?php

namespace App\Services\Courses;

use App\Models\Admin;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\CourseAssigned;

/** Gives a student access to a course (manual process done by admins). */
class EnrollmentService
{
    /** @return bool false when the student already had the course */
    public function assign(User $student, Course $course, ?Admin $by = null): bool
    {
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $student->id, 'course_id' => $course->id],
            ['assigned_by' => $by?->id, 'assigned_at' => now()],
        );

        if ($enrollment->wasRecentlyCreated) {
            $student->notify(new CourseAssigned($course));
        }

        return $enrollment->wasRecentlyCreated;
    }

    public function remove(User $student, Course $course): void
    {
        Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->delete();
    }
}
