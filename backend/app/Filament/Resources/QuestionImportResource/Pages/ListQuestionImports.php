<?php

namespace App\Filament\Resources\QuestionImportResource\Pages;

use App\Enums\ImportMethod;
use App\Filament\Resources\QuestionImportResource;
use App\Filament\Support\ImportActions;
use Filament\Resources\Pages\ListRecords;

class ListQuestionImports extends ListRecords
{
    protected static string $resource = QuestionImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportActions::template(),
            ImportActions::import(),
            ImportActions::import(fixed: ImportMethod::PdfAi),
        ];
    }
}
