<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Enums\ContentType;
use App\Enums\TestKind;
use App\Filament\Resources\CourseResource;
use App\Filament\Support\ContentForm;
use App\Models\Chapter;
use App\Models\ChapterContent;
use App\Models\CurriculumItem;
use App\Models\Subject;
use App\Models\Test;
use App\Services\Courses\CourseOutline;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

/**
 * THE COURSE BUILDER: an outline of the whole course, edited in place.
 *
 *   Course
 *    ├─ Subject ── Chapter ── Content (video, live class, PDF, notes, assignment, practice test)
 *    └─ Mock test           ← sits anywhere between subjects
 *
 * Everything is a compact row. Drag the ⋮⋮ handle to reorder (contents can be dragged between chapters).
 * Adding/editing opens a slide-over. All changes save immediately via App\Services\Courses\CourseOutline.
 * The page layout is resources/views/filament/course-builder/page.blade.php.
 */
class ManageCurriculum extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CourseResource::class;

    protected string $view = 'filament.course-builder.page';

    /** "New chapter" text boxes, keyed by subject id. */
    public array $newChapter = [];

    public string $newSubject = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    public function getBreadcrumb(): string
    {
        return 'Build course';
    }

    protected function getViewData(): array
    {
        $entries = $this->outline()->entries($this->getRecord());

        return [
            'entries' => $entries,
            'stats' => $this->outline()->stats($entries),
            'outline' => $this->outline(),
            'contentTypes' => collect(ContentType::cases())->map(fn (ContentType $t) => ['value' => $t->value, 'label' => $t->label(), 'icon' => ContentForm::ICONS[$t->value]]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('details')->label('Course details')->icon('heroicon-o-pencil-square')->color('gray')
                ->url(fn () => CourseResource::getUrl('edit', ['record' => $this->getRecord()])),
            Action::make('students')->label('Students')->icon('heroicon-o-user-group')->color('gray')
                ->url(fn () => CourseResource::getUrl('edit', ['record' => $this->getRecord()]).'#relation-manager'),
        ];
    }

    // ------------------------------------------------------------------
    // Modals (opened with mountAction('name', {...}) from the Blade view)
    // ------------------------------------------------------------------

    /** Add or edit one content item. arguments: chapter, type, id (when editing) */
    public function contentAction(): Action
    {
        return Action::make('content')
            ->slideOver()->modalWidth('3xl')
            ->modalHeading(fn (array $arguments) => (isset($arguments['id']) ? 'Edit ' : 'Add ').strtolower(ContentType::from($arguments['type'])->label()))
            ->modalSubmitActionLabel(fn (array $arguments) => isset($arguments['id']) ? 'Save changes' : 'Add to chapter')
            ->schema(fn (array $arguments) => ContentForm::schema(ContentType::from($arguments['type']), $this->getRecord()->id))
            // Editing: fill with the saved values. Adding: fill() with no data so field defaults apply.
            ->mountUsing(fn (Schema $schema, array $arguments) => $schema->fill(
                isset($arguments['id']) ? $this->outline()->contentData($this->findContent($arguments['id'])) : null,
            ))
            ->action(function (array $data, array $arguments) {
                $existing = isset($arguments['id']) ? $this->findContent($arguments['id']) : null;
                $chapter = $existing?->chapter ?? $this->findChapter($arguments['chapter']);
                $this->outline()->saveContent($chapter, ContentType::from($arguments['type']), $data, $existing);

                Notification::make()->title($existing ? 'Saved' : 'Added to “'.$chapter->title.'”')->success()->send();
            });
    }

    /** Edit a subject's name/description. arguments: id */
    public function subjectAction(): Action
    {
        return Action::make('subject')->modalHeading('Edit subject')->modalWidth('lg')
            ->schema([
                TextInput::make('title')->label('Subject name')->required()->maxLength(150)->autofocus(),
                Textarea::make('description')->rows(3),
            ])
            ->fillForm(fn (array $arguments) => $this->findSubject($arguments['id'])->only(['title', 'description']))
            ->action(fn (array $data, array $arguments) => $this->outline()->updateSubject($this->findSubject($arguments['id']), $data));
    }

    /** Rename a chapter. arguments: id */
    public function chapterAction(): Action
    {
        return Action::make('chapter')->modalHeading('Rename chapter')->modalWidth('lg')
            ->schema([TextInput::make('title')->label('Chapter name')->required()->maxLength(150)->autofocus()])
            ->fillForm(fn (array $arguments) => ['title' => $this->findChapter($arguments['id'])->title])
            ->action(fn (array $data, array $arguments) => $this->outline()->renameChapter($this->findChapter($arguments['id']), $data['title']));
    }

    /** Put a mock test on the course page. arguments: after (entry id to insert after, optional) */
    public function mockTestAction(): Action
    {
        $courseId = $this->getRecord()->id;

        return Action::make('mockTest')->modalHeading('Add a mock test to this course')->modalWidth('lg')->modalSubmitActionLabel('Add to course')
            ->schema([
                Select::make('test_id')->label('Mock test')->required()->searchable()->preload()
                    ->options(fn () => Test::where('course_id', $courseId)->where('kind', TestKind::Mock)
                        ->whereNotIn('id', CurriculumItem::where('course_id', $courseId)->where('item_type', 'test')->pluck('item_id'))
                        ->pluck('title', 'id'))
                    ->helperText('Choose an existing mock test, or create a new one with the + button. Add its questions afterwards with "Manage questions".')
                    ->createOptionModalHeading('New mock test')
                    ->createOptionForm(ContentForm::newTestFields())
                    ->createOptionUsing(fn (array $data) => Test::create($data + ['course_id' => $courseId, 'kind' => TestKind::Mock, 'is_published' => true])->getKey()),
            ])
            ->action(function (array $data, array $arguments) {
                $this->outline()->addMockTest($this->getRecord(), Test::where('course_id', $this->getRecord()->id)->findOrFail($data['test_id']), $arguments['after'] ?? null);
                Notification::make()->title('Mock test added to the course')->success()->send();
            });
    }

    /** Move a content item to another chapter. arguments: id */
    public function moveContentAction(): Action
    {
        return Action::make('moveContent')->modalHeading('Move to another chapter')->modalWidth('lg')->modalSubmitActionLabel('Move')
            ->schema([
                Select::make('chapter_id')->label('Move to')->required()->searchable()
                    ->options(fn () => Chapter::whereIn('subject_id', $this->getRecord()->subjects()->pluck('id'))->with('subject')->get()
                        ->mapWithKeys(fn ($c) => [$c->id => "{$c->subject->title} → {$c->title}"])),
            ])
            ->action(fn (array $data, array $arguments) => $this->outline()->moveContent($this->findContent($arguments['id']), $this->findChapter($data['chapter_id'])));
    }

    // ------------------------------------------------------------------
    // Inline actions (wire:click in the Blade view)
    // ------------------------------------------------------------------

    public function addSubject(): void
    {
        $title = trim($this->newSubject);
        if ($title === '') {
            return;
        }
        $subject = $this->outline()->addSubject($this->getRecord(), $title);
        $this->newSubject = '';
        $this->dispatch('builder-focus', target: 'subject-'.$subject->id);
    }

    /** Adds a chapter and scrolls to it, so the admin can add its first content straight away. */
    public function addChapter(int $subjectId): void
    {
        $title = trim($this->newChapter[$subjectId] ?? '');
        if ($title === '') {
            return;
        }
        $chapter = $this->outline()->addChapter($this->findSubject($subjectId), $title);
        $this->newChapter[$subjectId] = '';
        $this->dispatch('builder-focus', target: 'chapter-'.$chapter->id, openMenu: true);
    }

    public function deleteContent(int $id): void
    {
        $this->outline()->deleteContent($this->findContent($id));
    }

    public function deleteChapter(int $id): void
    {
        $this->outline()->deleteChapter($this->findChapter($id));
    }

    /** Subjects are deleted with their chapters and contents; mock tests are only taken off the course page. */
    public function removeEntry(int $entryId): void
    {
        $this->outline()->removeEntry(CurriculumItem::where('course_id', $this->getRecord()->id)->findOrFail($entryId));
    }

    // ---- drag and drop ----

    public function reorderEntries(array $ids): void
    {
        $this->outline()->reorderEntries($this->getRecord(), array_map(fn ($id) => (int) str_replace('entry-', '', $id), $ids));
    }

    public function arrangeChapters(int $subjectId, array $ids): void
    {
        $this->outline()->arrangeChapters($this->findSubject($subjectId), array_map(fn ($id) => (int) str_replace('chapter-', '', $id), $ids));
    }

    public function arrangeContents(int $chapterId, array $ids): void
    {
        $this->outline()->arrangeContents($this->findChapter($chapterId), array_map(fn ($id) => (int) str_replace('content-', '', $id), $ids));
    }

    // ------------------------------------------------------------------

    private function outline(): CourseOutline
    {
        return app(CourseOutline::class);
    }

    // Lookups are always scoped to this course, so ids from the browser can't touch other courses.

    private function findSubject(int $id): Subject
    {
        return $this->getRecord()->subjects()->findOrFail($id);
    }

    private function findChapter(int $id): Chapter
    {
        return Chapter::whereIn('subject_id', $this->getRecord()->subjects()->pluck('id'))->findOrFail($id);
    }

    private function findContent(int $id): ChapterContent
    {
        return ChapterContent::whereIn('chapter_id', Chapter::whereIn('subject_id', $this->getRecord()->subjects()->pluck('id'))->pluck('id'))->findOrFail($id);
    }
}
