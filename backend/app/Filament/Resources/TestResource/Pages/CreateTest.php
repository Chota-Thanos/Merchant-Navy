<?php

namespace App\Filament\Resources\TestResource\Pages;

use App\Filament\Resources\TestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTest extends CreateRecord
{
    protected static string $resource = TestResource::class;

    /** After creating a test, go straight to adding its questions. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('questions', ['record' => $this->getRecord()]);
    }
}
