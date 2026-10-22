<?php

namespace App\Filament\Support;

use App\Enums\ContentType;
use App\Enums\TestKind;
use App\Enums\VideoProvider;
use App\Models\Test;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/**
 * The form for each chapter content type (opened in a slide-over from the course builder).
 * To support a new type: add a case to App\Enums\ContentType, a branch here, and one in
 * App\Services\Courses\CourseOutline::saveMaterial().
 */
class ContentForm
{
    /** Icon and label used by the builder for each type. */
    public const ICONS = [
        'video' => 'heroicon-o-play-circle',
        'live_class' => 'heroicon-o-signal',
        'pdf' => 'heroicon-o-document-text',
        'note' => 'heroicon-o-document',
        'assignment' => 'heroicon-o-clipboard-document-check',
        'practice_test' => 'heroicon-o-bolt',
    ];

    public static function schema(ContentType $type, int $courseId): array
    {
        $title = TextInput::make('title')->label('Title shown to students')->required()->maxLength(150)->autofocus();

        return [$title, ...match ($type) {
            ContentType::Video => [
                Select::make('provider')->label('Hosted on')
                    ->options(collect(VideoProvider::cases())->mapWithKeys(fn ($p) => [$p->value => $p->name]))->default('youtube')->required(),
                TextInput::make('provider_ref')->label('Video link or ID')->required(),
                TextInput::make('duration_minutes')->label('Duration (minutes)')->numeric()->minValue(0),
            ],
            ContentType::LiveClass => [
                DateTimePicker::make('starts_at')->label('Starts at')->required(),
                TextInput::make('duration_minutes')->label('Duration (minutes)')->numeric()->default(60)->required(),
                Select::make('provider')->label('Streaming provider (optional)')->options(['agora' => 'Agora', 'youtube' => 'YouTube Live'])
                    ->helperText('Live streaming is connected in a later milestone. Students are reminded before the class starts.'),
            ],
            ContentType::Pdf => [
                FileUpload::make('file')->label('PDF file')->required()->acceptedFileTypes(['application/pdf'])
                    ->disk('local')->directory('pdfs')->maxSize(51200)
                    ->helperText('Stored privately. Students see watermarked pages only and can never download the file.'),
            ],
            ContentType::Note => [
                RichEditor::make('body')->label('Notes')->required()
                    ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('note-images')
                    ->helperText('Maths: write LaTeX between $ signs.'),
            ],
            ContentType::Assignment => [
                RichEditor::make('instructions')->required(),
                DateTimePicker::make('due_at')->label('Due date (optional)'),
            ],
            ContentType::PracticeTest => [
                Select::make('test_id')->label('Practice test')->required()->searchable()->preload()
                    ->options(fn () => Test::where('course_id', $courseId)->where('kind', TestKind::Practice)->pluck('title', 'id'))
                    ->helperText('Pick a test, or create a new one with the + button. Add its questions afterwards with "Manage questions".')
                    ->createOptionModalHeading('New practice test')
                    ->createOptionForm(self::newTestFields())
                    ->createOptionUsing(fn (array $data) => Test::create($data + ['course_id' => $courseId, 'kind' => TestKind::Practice, 'is_published' => true])->getKey()),
            ],
        }];
    }

    /** Fields to create a test on the spot (used for practice and mock tests). */
    public static function newTestFields(): array
    {
        return [
            TextInput::make('title')->required()->maxLength(150),
            TextInput::make('duration_minutes')->label('Duration (minutes)')->numeric()->required()->default(30)->minValue(1),
            TextInput::make('marks_correct')->label('Marks for a correct answer')->numeric()->required()->default(4),
            TextInput::make('marks_wrong')->label('Marks deducted for a wrong answer')->numeric()->required()->default(1),
        ];
    }
}
