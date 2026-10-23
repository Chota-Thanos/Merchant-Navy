<?php

namespace Tests\Feature;

use App\Enums\ContentType;
use App\Enums\TestKind;
use App\Models\Chapter;
use App\Models\ChapterContent;
use App\Models\Course;
use App\Models\Test;
use App\Models\User;
use App\Services\Courses\CourseOutline;
use App\Services\Courses\EnrollmentService;
use App\Services\Tests\QuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Progress: what counts as completed, and that it shows in the course, the subject and every item. */
class ProgressTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Chapter $chapter;

    private Test $practice;

    /** @var array<string,ChapterContent> */
    private array $items = [];

    protected function setUp(): void
    {
        parent::setUp();

        $outline = app(CourseOutline::class);
        $this->course = Course::create(['title' => 'C', 'slug' => 'c']);
        $subject = $outline->addSubject($this->course, 'Maths');
        $this->chapter = $outline->addChapter($subject, 'Numbers');

        $this->practice = Test::create(['course_id' => $this->course->id, 'title' => 'Practice', 'kind' => TestKind::Practice, 'duration_minutes' => 10, 'is_published' => true]);
        $question = app(QuestionService::class)->save([
            'body' => 'Q', 'explanation' => 'E', 'correct' => 2, 'option_1' => 'A', 'option_2' => 'B', 'option_3' => 'C', 'option_4' => 'D',
        ]);
        $this->practice->questions()->attach($question->id, ['position' => 1]);

        $this->items['note'] = $outline->saveContent($this->chapter, ContentType::Note, ['title' => 'Notes', 'body' => '<p>text</p>']);
        $this->items['test'] = $outline->saveContent($this->chapter, ContentType::PracticeTest, ['title' => 'Practice test', 'test_id' => $this->practice->id]);
        $this->items['assignment'] = $outline->saveContent($this->chapter, ContentType::Assignment, ['title' => 'Homework', 'instructions' => '<p>do it</p>']);

        $student = User::factory()->create();
        app(EnrollmentService::class)->assign($student, $this->course);
        Sanctum::actingAs($student);
    }

    private function percent(): int
    {
        return $this->getJson("/api/v1/courses/{$this->course->id}")->assertOk()->json('data.progressPercent');
    }

    private function completed(string $item): bool
    {
        return (bool) $this->getJson('/api/v1/contents/'.$this->items[$item]->id)->assertOk()->json('data.content.completed');
    }

    public function test_nothing_is_complete_at_first(): void
    {
        $this->assertSame(0, $this->percent());
        $this->assertFalse($this->completed('test'));
    }

    public function test_marking_a_note_complete_moves_the_progress(): void
    {
        $this->postJson('/api/v1/contents/'.$this->items['note']->id.'/complete')->assertOk();

        $this->assertTrue($this->completed('note'));
        $this->assertSame(33, $this->percent()); // 1 of 3 items
    }

    public function test_submitting_a_practice_test_completes_its_lesson_without_any_button(): void
    {
        $attempt = $this->postJson("/api/v1/tests/{$this->practice->id}/attempts")->assertSuccessful()->json('data.attemptId');
        $this->postJson("/api/v1/attempts/{$attempt}/submit")->assertSuccessful();

        $this->assertTrue($this->completed('test'));
        $this->assertSame(33, $this->percent());

        // the subject card shows it too
        $subject = $this->getJson("/api/v1/courses/{$this->course->id}")->json('data.curriculum.0.subject');
        $this->assertSame(33, $subject['progressPercent']);
    }

    public function test_handing_in_an_assignment_completes_it(): void
    {
        Storage::fake('local');
        $this->post('/api/v1/contents/'.$this->items['assignment']->id.'/submission', ['file' => UploadedFile::fake()->create('work.pdf', 50, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertSuccessful();

        $this->assertTrue($this->completed('assignment'));
    }

    public function test_one_students_work_never_counts_for_another(): void
    {
        $attempt = $this->postJson("/api/v1/tests/{$this->practice->id}/attempts")->json('data.attemptId');
        $this->postJson("/api/v1/attempts/{$attempt}/submit")->assertSuccessful();

        $other = User::factory()->create();
        app(EnrollmentService::class)->assign($other, $this->course);
        Sanctum::actingAs($other);

        $this->assertSame(0, $this->percent());
        $this->assertFalse($this->completed('test'));
    }
}
