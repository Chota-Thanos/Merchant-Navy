<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Enums\ImportMethod;
use App\Filament\Resources\QuestionResource;
use App\Filament\Support\ImportActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New question'),
            ImportActions::import(),
            ImportActions::import(fixed: ImportMethod::PdfAi),
            ImportActions::template(),
        ];
    }
}
