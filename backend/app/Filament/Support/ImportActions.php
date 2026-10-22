<?php

namespace App\Filament\Support;

use App\Enums\ImportMethod;
use App\Filament\Resources\QuestionImportResource;
use App\Models\Test;
use App\Services\Imports\Ai\AiReaderManager;
use App\Services\Imports\ExcelTemplate;
use App\Services\Imports\QuestionImportService;
use App\Services\Pdf\PdfTool;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;

/**
 * The "Import questions" buttons. An upload becomes a REVIEW LIST (QuestionImportResource → ReviewImport):
 * nothing is added to the question bank until the admin approves it there.
 */
class ImportActions
{
    /**
     * @param  ImportMethod|null  $fixed  null = the admin chooses (PDF / PDF with AI / Excel)
     * @param  Test|null  $test  the test the questions are meant for (preselected on the review page)
     */
    public static function import(?Test $test = null, ?ImportMethod $fixed = null): Action
    {
        $url = null;

        return Action::make($fixed ? 'importAi' : 'import')
            ->label($fixed ? 'Parse PDF with AI' : 'Import from PDF or Excel')
            ->icon($fixed ? 'heroicon-m-sparkles' : 'heroicon-m-arrow-up-tray')
            ->color($fixed ? 'primary' : 'gray')
            ->modalHeading($fixed ? 'Parse a PDF with AI' : 'Import questions')
            ->modalDescription('The questions are read into a list for you to review. Nothing is added to a test until you approve it.')
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Read the file')
            ->schema([
                Radio::make('method')->label('How should the file be read?')->required()
                    ->default($fixed?->value ?? ImportMethod::Pdf->value)
                    ->visible(! $fixed)
                    ->options([
                        ImportMethod::Pdf->value => 'PDF — automatic reader (instant). Best for PDFs that follow a clear layout: numbered questions, options a–d, an answer line.',
                        ImportMethod::PdfAi->value => 'PDF — with AI (a few minutes; uses your Gemini, OpenAI or Claude key). Reads any layout and typeset formulas: fractions, roots, vectors.',
                        ImportMethod::Excel->value => 'Excel / CSV — one question per row. Use the template.',
                    ]),
                FileUpload::make('file')->label($fixed ? 'PDF file' : 'File')->required()
                    ->disk('local')->directory('question-imports')->storeFileNamesIn('original_name')->maxSize(51200)
                    ->acceptedFileTypes($fixed
                        ? ['application/pdf']
                        : ['application/pdf', 'text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->helperText($fixed
                        ? 'Maths, tables and layout are understood. Up to about 100 pages per file works best.'
                        : 'PDF, .xlsx or .csv, up to 50 MB.'),
            ])
            ->action(function (array $data, Action $action) use ($test, $fixed, &$url) {
                $method = $fixed ?? ImportMethod::from($data['method']);
                $name = $data['original_name'] ?? basename($data['file']);
                $isPdf = str_ends_with(strtolower($name), '.pdf');

                // The chosen method must fit the file, and the server must be set up for it
                $problem = match (true) {
                    $method === ImportMethod::Excel && $isPdf => 'This is a PDF. Choose one of the PDF options.',
                    $method !== ImportMethod::Excel && ! $isPdf => 'This is not a PDF. Choose “Excel / CSV”.',
                    $method !== ImportMethod::Excel && ! app(PdfTool::class)->available() => 'Reading PDFs needs Python with PyMuPDF on the server: run “pip install pymupdf” (see README).',
                    $method === ImportMethod::PdfAi && ! app(AiReaderManager::class)->configured() => AiReaderManager::missingKeyMessage(),
                    default => null,
                };
                if ($problem) {
                    Notification::make()->title('Cannot read this file')->body($problem)->danger()->send();
                    $action->halt();
                }

                $import = app(QuestionImportService::class)->start($data['file'], $name, $method, $test, auth('admin')->user());
                $url = QuestionImportResource::getUrl('review', ['record' => $import]);
            })
            ->successRedirectUrl(function () use (&$url) {
                return $url; // set by the action above (an arrow function would copy it too early)
            });
    }

    /** Downloads the blank Excel template. */
    public static function template(): Action
    {
        return Action::make('template')->label('Excel template')->icon('heroicon-m-arrow-down-tray')->color('gray')
            ->action(fn () => response()->download((new ExcelTemplate)->build(), 'question-template.xlsx')->deleteFileAfterSend());
    }
}
