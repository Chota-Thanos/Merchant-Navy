<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateStudentCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_student_with_a_completed_profile_and_assigns_all_courses(): void
    {
        $course = Course::create(['title' => 'Demo', 'slug' => 'demo', 'description' => 'x']);

        $this->artisan('lms:create-student', ['phone' => '+91 99999 00001', '--name' => 'Test Student', '--all-courses' => true])
            ->assertSuccessful();

        $student = User::where('phone', '9999900001')->firstOrFail();
        $this->assertFalse($student->needsProfile());
        $this->assertSame('Test Student', $student->name);
        $this->assertTrue($student->courses()->whereKey($course->id)->exists());

        // Running it again updates instead of failing.
        $this->artisan('lms:create-student', ['phone' => '9999900001'])->assertSuccessful();
        $this->assertSame(1, User::where('phone', '9999900001')->count());
    }

    public function test_rejects_a_phone_number_that_is_too_short(): void
    {
        $this->artisan('lms:create-student', ['phone' => '123'])->assertFailed();
    }
}
