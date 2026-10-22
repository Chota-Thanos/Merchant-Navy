<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Test attempts of this student. */
class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Test results';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('test.title')->label('Test')->weight('bold'),
                TextColumn::make('attempt_no')->label('Attempt'),
                TextColumn::make('score'),
                TextColumn::make('correct')->color('success'),
                TextColumn::make('incorrect')->color('danger'),
                TextColumn::make('skipped'),
                TextColumn::make('time_taken_seconds')->label('Time')->formatStateUsing(fn ($state) => $state === null ? '—' : gmdate('H:i:s', $state)),
                TextColumn::make('submitted_at')->dateTime('d M Y, h:i A')->placeholder('In progress'),
            ])
            ->defaultSort('id', 'desc');
    }
}
