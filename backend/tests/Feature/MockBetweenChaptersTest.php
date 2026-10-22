<?php

namespace Tests\Feature;

use App\Enums\ContentType;
use App\Enums\TestKind;
use App\Filament\Resources\CourseResource\Pages\ManageCurriculum;
use App\Models\Admin;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use App\Services\Courses\CourseOutline;
use App\Services\Courses\EnrollmentService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/** A mock test can sit between the chapters of a subject, in any position, and students see it there. */
class MockBetweenChaptersTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Subject $subject;

    private array $chapters = [];

    private Test $mock;

    protected function setUp(): void
    {
        parent::setUp();

        $outline = app(CourseOutline::class);
        $this->course = Course::create(['title' => 'C', 'slug' => 'c']);
        $this->subject = $outline->addSubject($this->course, 'Maths');
        foreach (['One', 'Two', 'Three'] as $title) {
            $this->chapters[$title] = $outline->addChapter($this->subject, $title);
        }
        $this->mock = Test::create(['course_id' => $this->course->id, 'title' => 'Mock A', 'kind' => TestKind::Mock, 'duration_minutes' => 10, 'is_published' => true]);
    }

    private function titles(): array
    {
        return $this->subject->chapters()->get()->map(fn ($c) => $c->isMockTest() ? 'MOCK' : $c->title)->all();
    }

    public function test_mock_test_goes_right_after_the_chosen_chapter(): void
    {
        app(CourseOutline::class)->addMockTestToSubject($this->subject, $this->mock, $this->chapters['One']->id);

        $this->assertSame(['One', 'MOCK', 'Two', 'Three'], $this->titles());
    }

    public function test_without_a_position_it_goes_to_the_end_and_can_be_dragged_anywhere(): void
    {
        $outline = app(CourseOutline::class);
        $entry = $outline->addMockTestToSubject($this->subject, $this->mock);
        $this->assertSame(['One', 'Two', 'Three', 'MOCK'], $this->titles());

        // Dragging in the builder sends the new order of the whole list.
        $outline->arrangeChapters($this->subject, [$entry->id, $this->chapters['One']->id, $this->chapters['Two']->id, $this->chapters['Three']->id]);
        $this->assertSame(['MOCK', 'One', 'Two', 'Three'], $this->titles());
    }

    public function test_a_placed_mock_test_is_not_offered_again(): void
    {
        $outline = app(CourseOutline::class);
        $this->assertArrayHasKey($this->mock->id, $outline->unplacedMockTests($this->course)->all());

        $outline->addMockTestToSubject($this->subject, $this->mock);

        $this->assertArrayNotHasKey($this->mock->id, $outline->unplacedMockTests($this->course)->all());
    }

    public function test_students_get_it_in_order_and_chapter_counts_ignore_it(): void
    {
        app(CourseOutline::class)->addMockTestToSubject($this->subject, $this->mock, $this->chapters['Two']->id);
        $student = User::factory()->create();
        app(EnrollmentService::class)->assign($student, $this->course);
        Sanctum::actingAs($student);

        $chapters = $this->getJson("/api/v1/subjects/{$this->subject->id}")->assertOk()->json('data.chapters');
        $this->assertSame(['One', 'Two', 'Mock A', 'Three'], array_column($chapters, 'title'));
        $this->assertSame([null, null, $this->mock->id, null], array_map(fn ($c) => $c['test']['id'] ?? null, $chapters));

        $this->getJson("/api/v1/courses/{$this->course->id}")->assertOk()->assertJsonPath('data.chaptersCount', 3);
    }

    public function test_an_unpublished_mock_test_is_hidden_from_students(): void
    {
        app(CourseOutline::class)->addMockTestToSubject($this->subject, $this->mock);
        $this->mock->update(['is_published' => false]);
        $student = User::factory()->create();
        app(EnrollmentService::class)->assign($student, $this->course);
        Sanctum::actingAs($student);

        $chapters = $this->getJson("/api/v1/subjects/{$this->subject->id}")->assertOk()->json('data.chapters');

        $this->assertSame(['One', 'Two', 'Three'], array_column($chapters, 'title'));
    }

    public function test_builder_shows_it_between_the_chapters(): void
    {
        app(CourseOutline::class)->addMockTestToSubject($this->subject, $this->mock, $this->chapters['One']->id);
        $this->actingAs(Admin::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret-password']), 'admin');

        $html = $this->get("/admin/courses/{$this->course->id}/curriculum")->assertOk()->getContent();

        $this->assertStringContainsString('Mock A', $html);
        $this->assertMatchesRegularExpression('#<a\b[^>]*href="[^"]*/tests/'.$this->mock->id.'/questions"#', $html);
        $this->assertLessThan(strpos($html, 'Two'), strpos($html, 'Mock A'), 'the mock test is listed before chapter Two');
    }

    private function builder()
    {
        $this->actingAs(Admin::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret-password']), 'admin');
        Filament::setCurrentPanel('admin');

        return Livewire::test(ManageCurriculum::class, ['record' => $this->course->id]);
    }

    public function test_the_popup_can_create_a_brand_new_mock_test_in_place(): void
    {
        $this->builder()->callAction('mockTest', [
            'mode' => 'new', 'new_title' => 'Fresh Mock', 'new_duration_minutes' => 45, 'new_marks_correct' => 3, 'new_marks_wrong' => 1,
        ], ['subject' => $this->subject->id, 'afterChapter' => $this->chapters['One']->id])->assertHasNoActionErrors();

        $test = Test::where('title', 'Fresh Mock')->firstOrFail();
        $this->assertSame([TestKind::Mock, 45], [$test->kind, $test->duration_minutes]);
        $this->assertTrue($test->is_published);
        $this->assertSame(['One', 'MOCK', 'Two', 'Three'], $this->titles());
    }

    public function test_the_popup_can_add_an_existing_mock_test(): void
    {
        $this->builder()->callAction('mockTest', ['mode' => 'existing', 'test_id' => $this->mock->id], ['subject' => $this->subject->id])
            ->assertHasNoActionErrors();

        $this->assertSame(['One', 'Two', 'Three', 'MOCK'], $this->titles());
    }

    public function test_creating_a_new_mock_test_needs_a_name(): void
    {
        $this->builder()
            ->callAction('mockTest', ['mode' => 'new', 'new_duration_minutes' => 30, 'new_marks_correct' => 4, 'new_marks_wrong' => 1], ['subject' => $this->subject->id])
            ->assertHasActionErrors(['new_title' => 'required']);
    }

    public function test_choosing_existing_needs_a_test_to_be_picked(): void
    {
        $this->builder()
            ->callAction('mockTest', ['mode' => 'existing'], ['subject' => $this->subject->id])
            ->assertHasActionErrors(['test_id' => 'required']);
    }

    public function test_a_new_practice_test_is_created_from_the_content_form(): void
    {
        $this->builder()->callAction('content', [
            'title' => 'Quick Practice', 'mode' => 'new', 'new_duration_minutes' => 10, 'new_marks_correct' => 4, 'new_marks_wrong' => 1,
        ], ['chapter' => $this->chapters['One']->id, 'type' => ContentType::PracticeTest->value])->assertHasNoActionErrors();

        $test = Test::where('title', 'Quick Practice')->firstOrFail();
        $this->assertSame(TestKind::Practice, $test->kind);
        $this->assertTrue($this->chapters['One']->contents()->where('contentable_id', $test->id)->exists());
    }
}
