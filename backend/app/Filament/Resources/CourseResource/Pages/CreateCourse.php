<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    /** After creating a course, go straight to building its content. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('curriculum', ['record' => $this->getRecord()]);
    }
}
