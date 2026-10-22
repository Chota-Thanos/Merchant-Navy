<?php

namespace Tests\Feature;

use App\Enums\ContentType;
use App\Enums\TestKind;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Notifications\LiveClassStartingSoon;
use App\Services\Courses\CourseOutline;
use App\Services\Courses\EnrollmentService;
use App\Services\Tests\QuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The student API end to end: access control, courses, content, tests, results, notifications. */
class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Chapter $chapter;

    private Test $mock;

    /** @var array<int,Question> */
    private array $questions = [];

    protected function setUp(): void
    {
        parent::setUp();

        $outline = app(CourseOutline::class);
        $this->course = Course::create(['title' => 'IMU-CET', 'slug' => 'imu-cet']);
        $subject = $outline->addSubject($this->course, 'Physics');
        $this->chapter = $outline->addChapter($subject, 'Units');
        $outline->saveContent($this->chapter, ContentType::Note, ['title' => 'Quick notes', 'body' => '<p>one two three</p>']);

        // Mock test with 3 questions; the correct option of each is option 2 (position 2).
        $this->mock = Test::create(['course_id' => $this->course->id, 'title' => 'Mock 1', 'kind' => TestKind::Mock,
            'duration_minutes' => 10, 'marks_correct' => 4, 'marks_wrong' => 1, 'is_published' => true]);
        foreach (range(1, 3) as $n) {
            $q = app(QuestionService::class)->save([
                'body' => "Question {$n}", 'explanation' => "Because {$n}", 'correct' => 2,
                'option_1' => 'A', 'option_2' => 'B', 'option_3' => 'C', 'option_4' => 'D',
            ]);
            $this->mock->questions()->attach($q->id, ['position' => $n]);
            $this->questions[] = $q;
        }
        $outline->addMockTest($this->course, $this->mock);
    }

    private function student(bool $enrolled = true, ?string $phone = null): User
    {
        $user = User::factory()->create($phone ? ['phone' => $phone] : []);
        if ($enrolled) {
            app(EnrollmentService::class)->assign($user, $this->course);
        }

        return $user;
    }

    /** option id of position `$n` for question `$q` */
    private function option(Question $q, int $n): int
    {
        return $q->options()->where('position', $n)->value('id');
    }

    private function attempt(User $user, array $picks): string
    {
        $session = $this->actingAs($user, 'sanctum')->postJson("/api/v1/tests/{$this->mock->id}/attempts")->assertOk()->json('data');
        foreach ($picks as $i => $position) {
            $this->putJson("/api/v1/attempts/{$session['attemptId']}/answers/{$this->questions[$i]->id}", [
                'selectedOptionId' => $position ? $this->option($this->questions[$i], $position) : null,
                'markedForReview' => false, 'visited' => true,
            ])->assertOk();
        }
        $this->postJson("/api/v1/attempts/{$session['attemptId']}/submit")->assertOk();

        return $session['attemptId'];
    }

    // ------------------------------------------------------------------ access

    public function test_student_only_sees_assigned_courses(): void
    {
        $other = Course::create(['title' => 'Secret', 'slug' => 'secret']);
        $student = $this->student();

        $ids = collect($this->actingAs($student, 'sanctum')->getJson('/api/v1/courses')->assertOk()->json('data'))->pluck('id');
        $this->assertSame([$this->course->id], $ids->all());
        $this->getJson("/api/v1/courses/{$other->id}")->assertNotFound();
    }

    public function test_unassigned_student_gets_nothing(): void
    {
        $this->actingAs($this->student(false), 'sanctum');

        $this->getJson('/api/v1/courses')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/courses/{$this->course->id}")->assertNotFound();
        $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->assertNotFound();
        $this->getJson('/api/v1/mock-tests')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_requires_login(): void
    {
        $this->getJson('/api/v1/courses')->assertUnauthorized();
    }

    // ------------------------------------------------------------------ learning

    public function test_course_page_lists_subjects_and_mock_tests_in_order(): void
    {
        $this->actingAs($this->student(), 'sanctum');

        $course = $this->getJson("/api/v1/courses/{$this->course->id}")->assertOk()->json('data');
        $this->assertSame(['subject', 'test'], array_column($course['curriculum'], 'kind'));
        $this->assertSame(3, $course['curriculum'][1]['test']['questionsCount']);
        $this->assertSame(12.0, (float) $course['curriculum'][1]['test']['totalMarks']);
    }

    public function test_content_can_be_opened_and_completed(): void
    {
        $this->actingAs($this->student(), 'sanctum');
        $subject = $this->course->subjects()->first();
        $content = $this->chapter->contents()->first();

        $this->getJson("/api/v1/subjects/{$subject->id}")->assertOk()->assertJsonPath('data.chapters.0.contents.0.type', 'note');
        $this->getJson("/api/v1/contents/{$content->id}")->assertOk()->assertJsonPath('data.content.readMinutes', 1);
        $this->assertSame(0, $this->getJson("/api/v1/courses/{$this->course->id}")->json('data.progressPercent'));

        $this->postJson("/api/v1/contents/{$content->id}/complete")->assertOk();
        $this->assertSame(100, $this->getJson("/api/v1/courses/{$this->course->id}")->json('data.progressPercent'));
    }

    public function test_a_chapter_can_hold_any_number_of_tests_pdfs_and_other_content(): void
    {
        $outline = app(CourseOutline::class);
        $tests = collect(range(1, 3))->map(fn ($n) => Test::create([
            'course_id' => $this->course->id, 'title' => "Practice {$n}", 'kind' => TestKind::Practice,
            'duration_minutes' => 10, 'is_published' => true,
        ]));
        foreach ($tests as $test) {
            $test->questions()->attach($this->questions[0]->id, ['position' => 0]);
            $outline->saveContent($this->chapter, ContentType::PracticeTest, ['title' => $test->title, 'test_id' => $test->id]);
        }
        $outline->saveContent($this->chapter, ContentType::PracticeTest, ['title' => 'Practice 1 again', 'test_id' => $tests[0]->id]); // even the same test twice
        foreach (range(1, 4) as $n) {
            $outline->saveContent($this->chapter, ContentType::Pdf, ['title' => "PDF {$n}", 'file' => "pdfs/{$n}.pdf"]);
        }

        $contents = $this->chapter->contents()->get();
        $this->assertSame(3 + 1 + 4 + 1, $contents->count()); // + the note from setUp
        $this->assertSame(4, $contents->where('type', ContentType::PracticeTest)->count());
        $this->assertSame(4, $contents->where('type', ContentType::Pdf)->count());

        $subject = $this->course->subjects()->first();
        $this->actingAs($this->student(), 'sanctum')->getJson("/api/v1/subjects/{$subject->id}")
            ->assertOk()->assertJsonCount(9, 'data.chapters.0.contents');
    }

    public function test_unpublished_tests_are_hidden(): void
    {
        $this->mock->update(['is_published' => false]);
        $this->actingAs($this->student(), 'sanctum');

        $this->getJson('/api/v1/mock-tests')->assertJsonCount(0, 'data');
        $this->assertSame(['subject'], array_column($this->getJson("/api/v1/courses/{$this->course->id}")->json('data.curriculum'), 'kind'));
    }

    public function test_assignment_submission_is_stored_privately(): void
    {
        Storage::fake('local');
        $assignment = app(CourseOutline::class)->saveContent($this->chapter, ContentType::Assignment, ['title' => 'Worksheet', 'instructions' => 'Solve']);
        $this->actingAs($this->student(), 'sanctum');

        $this->postJson("/api/v1/contents/{$assignment->id}/submission", ['file' => UploadedFile::fake()->create('answers.pdf', 100, 'application/pdf')])
            ->assertOk()->assertJsonStructure(['data' => ['submittedAt']]);
        $this->assertNotNull($this->getJson("/api/v1/contents/{$assignment->id}")->json('data.content.submittedAt'));
        $this->postJson("/api/v1/contents/{$assignment->id}/submission", ['file' => UploadedFile::fake()->create('virus.exe', 10)])->assertUnprocessable();
    }

    // ------------------------------------------------------------------ tests & results

    public function test_attempt_flow_scores_and_ranks(): void
    {
        $student = $this->student();
        $this->actingAs($student, 'sanctum');

        $session = $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->assertOk()->json('data');
        $this->assertCount(3, $session['test']['questions']);
        // The answer must never reach the browser while the test is running
        $this->assertStringNotContainsString('is_correct', json_encode($session));
        $this->assertStringNotContainsString('correctOptionId', json_encode($session));

        $id = $this->attempt($student, [2, 1, null]); // right, wrong, skipped → 4 - 1 = 3
        $result = $this->getJson("/api/v1/attempts/{$id}/result")->assertOk()->json('data');

        $this->assertEquals(3, $result['score']);
        $this->assertSame([1, 1, 1], [$result['correct'], $result['incorrect'], $result['skipped']]);
        $this->assertSame(50, $result['accuracy']);
        $this->assertSame(1, $result['rank']);
        $this->assertCount(3, $result['solutions']);
        $this->assertSame($this->option($this->questions[0], 2), $result['solutions'][0]['correctOptionId']);
        $this->assertNull($result['solutions'][2]['selectedOptionId']);
        $this->assertSame(1, $this->getJson('/api/v1/results')->json('data.0.attemptNo'));
    }

    public function test_open_attempt_is_resumed_with_saved_answers(): void
    {
        $this->actingAs($this->student(), 'sanctum');
        $first = $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->json('data');
        $this->putJson("/api/v1/attempts/{$first['attemptId']}/answers/{$this->questions[0]->id}", [
            'selectedOptionId' => $this->option($this->questions[0], 3), 'markedForReview' => true, 'visited' => true,
        ])->assertOk();

        $again = $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->json('data');
        $this->assertSame($first['attemptId'], $again['attemptId']);
        $this->assertSame($first['deadline'], $again['deadline']);
        $this->assertTrue($again['answers'][$this->questions[0]->id]['markedForReview']);
    }

    public function test_ranking_uses_first_attempts_only(): void
    {
        $a = $this->student(true, '9000000001');
        $b = $this->student(true, '9000000002');

        $this->attempt($a, [2, 2, 2]);                      // a: 12
        $bFirst = $this->attempt($b, [2, 2, null]);         // b: 8 → rank 2 of 2
        $this->assertSame(2, $this->getJson("/api/v1/attempts/{$bFirst}/result")->json('data.rank'));

        $bSecond = $this->attempt($b, [2, 2, 2]);           // b reattempts and scores 12
        $result = $this->getJson("/api/v1/attempts/{$bSecond}/result")->json('data');
        $this->assertSame(2, $result['attemptNo']);
        $this->assertSame(1, $result['rank']);              // would tie 'a' for first, not push 'a' down
        $this->assertSame(2, $result['totalCandidates']);

        $aResult = $this->actingAs($a, 'sanctum')->getJson('/api/v1/results')->json('data.0');
        $this->assertSame(1, $aResult['rank']);             // a's rank is unaffected by b's reattempt
    }

    public function test_other_students_attempts_are_not_visible(): void
    {
        $owner = $this->student(true, '9000000001');
        $id = $this->attempt($owner, [2, 2, 2]);

        $this->actingAs($this->student(true, '9000000002'), 'sanctum');
        $this->getJson("/api/v1/attempts/{$id}/result")->assertNotFound();
        $this->putJson("/api/v1/attempts/{$id}/answers/{$this->questions[0]->id}", ['selectedOptionId' => null])->assertNotFound();
    }

    public function test_result_is_not_available_before_submitting(): void
    {
        $this->actingAs($this->student(), 'sanctum');
        $id = $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->json('data.attemptId');

        $this->getJson("/api/v1/attempts/{$id}/result")->assertNotFound();
    }

    public function test_expired_attempts_are_auto_submitted_and_reject_late_answers(): void
    {
        $student = $this->student();
        $this->actingAs($student, 'sanctum');
        $id = $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->json('data.attemptId');
        $this->putJson("/api/v1/attempts/{$id}/answers/{$this->questions[0]->id}", ['selectedOptionId' => $this->option($this->questions[0], 2)])->assertOk();

        $this->travel(11)->minutes();
        $this->artisan('attempts:auto-submit')->assertSuccessful();

        $attempt = TestAttempt::firstWhere('uuid', $id);
        $this->assertSame('auto_submitted', $attempt->status->value);
        $this->assertEquals(4, $attempt->score);
        $this->assertSame(600, $attempt->time_taken_seconds); // clock stopped at the 10-minute limit
        $this->putJson("/api/v1/attempts/{$id}/answers/{$this->questions[1]->id}", ['selectedOptionId' => null])->assertStatus(409);
    }

    public function test_returning_after_time_is_up_starts_a_fresh_attempt(): void
    {
        $this->actingAs($this->student(), 'sanctum');
        $old = $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->json('data.attemptId');

        $this->travel(15)->minutes();
        $new = $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->json('data');

        $this->assertNotSame($old, $new['attemptId']);
        $this->assertSame('auto_submitted', TestAttempt::firstWhere('uuid', $old)->status->value);
    }

    public function test_answers_must_belong_to_the_question(): void
    {
        $this->actingAs($this->student(), 'sanctum');
        $id = $this->postJson("/api/v1/tests/{$this->mock->id}/attempts")->json('data.attemptId');

        // an option of question 2 sent for question 1
        $this->putJson("/api/v1/attempts/{$id}/answers/{$this->questions[0]->id}", ['selectedOptionId' => $this->option($this->questions[1], 1)])->assertStatus(422);
    }

    public function test_test_without_questions_cannot_be_started(): void
    {
        $empty = Test::create(['course_id' => $this->course->id, 'title' => 'Empty', 'kind' => TestKind::Practice, 'duration_minutes' => 5, 'is_published' => true]);

        $this->actingAs($this->student(), 'sanctum')->postJson("/api/v1/tests/{$empty->id}/attempts")->assertUnprocessable();
    }

    // ------------------------------------------------------------------ notifications

    public function test_notifications_for_assignment_and_results(): void
    {
        $student = $this->student();
        $this->attempt($student, [2, 2, 2]);

        $items = $this->getJson('/api/v1/notifications')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing(['course_assigned', 'result'], array_column($items, 'type'));
        $this->assertFalse($items[0]['read']);

        $this->postJson('/api/v1/notifications/read')->assertOk();
        $this->assertTrue($this->getJson('/api/v1/notifications')->json('data.0.read'));
    }

    public function test_live_class_reminder_goes_to_enrolled_students_once(): void
    {
        Notification::fake();
        $enrolled = $this->student(true, '9000000001');
        $outsider = $this->student(false, '9000000002');
        app(CourseOutline::class)->saveContent($this->chapter, ContentType::LiveClass, ['title' => 'Doubt class', 'starts_at' => now()->addMinutes(10), 'duration_minutes' => 60]);

        $this->artisan('live-classes:remind')->assertSuccessful();
        $this->artisan('live-classes:remind')->assertSuccessful(); // second run must not notify again

        Notification::assertSentToTimes($enrolled, LiveClassStartingSoon::class, 1);
        Notification::assertNotSentTo($outsider, LiveClassStartingSoon::class);
        $this->assertNotNull(LiveClass::first()->reminder_sent_at);
    }

    // ------------------------------------------------------------------ protected PDFs

    public function test_pdf_pages_are_watermarked_and_links_are_protected(): void
    {
        Storage::fake('local');
        $pdf = app(CourseOutline::class)->saveContent($this->chapter, ContentType::Pdf, ['title' => 'Notes PDF', 'file' => 'pdfs/notes.pdf']);
        $document = $pdf->contentable;

        // Pretend background processing already produced one page image
        $page = imagecreatetruecolor(400, 560);
        imagefill($page, 0, 0, imagecolorallocate($page, 255, 255, 255));
        ob_start();
        imagepng($page);
        Storage::disk('local')->put("pdf-pages/{$document->id}/1.png", ob_get_clean());
        $document->update(['page_count' => 1, 'processed_at' => now()]);

        $student = $this->student(true, '9000000001');
        $urls = $this->actingAs($student, 'sanctum')->getJson("/api/v1/contents/{$pdf->id}/pages")->assertOk()->json('data');
        $this->assertCount(1, $urls);

        $response = $this->get($urls[0])->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertNotSame(Storage::disk('local')->get("pdf-pages/{$document->id}/1.png"), $response->getContent(), 'watermark must change the image');

        $this->get(preg_replace('/signature=[^&]+/', 'signature=bad', $urls[0]))->assertForbidden();   // tampered link
        $this->get("/api/v1/pdf-pages/{$pdf->id}/1")->assertForbidden();                               // unsigned link

        // access removed → even a still-valid link stops working
        app(EnrollmentService::class)->remove($student, $this->course);
        $this->get($urls[0])->assertNotFound();
    }

    public function test_pdf_still_processing_returns_no_pages(): void
    {
        Storage::fake('local');
        $pdf = app(CourseOutline::class)->saveContent($this->chapter, ContentType::Pdf, ['title' => 'Notes PDF', 'file' => 'pdfs/notes.pdf']);

        $this->actingAs($this->student(), 'sanctum')->getJson("/api/v1/contents/{$pdf->id}/pages")->assertOk()->assertJsonPath('data', []);
    }
}
