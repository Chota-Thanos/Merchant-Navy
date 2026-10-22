<?php

namespace App\Filament\Support;

use App\Models\Chapter;
use App\Models\Subject;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

/**
 * The question editor, used everywhere questions are created or edited
 * (Question bank, test builder). Saved by App\Services\Tests\QuestionService.
 *
 * Maths: type LaTeX between dollar signs: $x^2$ (inline) or $$\frac{a}{b}$$ (own line).
 */
class QuestionForm
{
    public static function schema(): array
    {
        $option = fn (int $n) => Textarea::make("option_{$n}")
            ->label('Option '.chr(64 + $n))
            ->required()
            ->rows(2)
            ->autosize();

        return [
            Section::make('Question')
                ->description('Maths uses LaTeX between dollar signs: $x^2 + y^2$ (inside text) or $$\frac{a}{b}$$ (on its own line).')
                ->schema([
                    RichEditor::make('body')->hiddenLabel()->required()
                        ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('question-images'),
                ]),
            Section::make('Options')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([$option(1), $option(2), $option(3), $option(4)]),
                    Radio::make('correct')->label('Which option is correct?')->required()->inline()
                        ->options(['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D']),
                ]),
            Section::make('Solution')
                ->description('Shown to the student after the test.')
                ->collapsible()
                ->schema([
                    RichEditor::make('explanation')->hiddenLabel()
                        ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('question-images'),
                ]),
            Section::make('Tags (optional)')
                ->description('Helps you find the question later.')
                ->collapsible()->collapsed()
                ->columns(3)
                ->schema([
                    Select::make('subject_id')->label('Subject')->searchable()
                        ->options(fn () => Subject::with('course')->get()->mapWithKeys(fn ($s) => [$s->id => "{$s->course->title} — {$s->title}"])),
                    Select::make('chapter_id')->label('Chapter')->searchable()->options(fn () => Chapter::pluck('title', 'id')),
                    Select::make('difficulty')->options(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard']),
                ]),
        ];
    }
}
