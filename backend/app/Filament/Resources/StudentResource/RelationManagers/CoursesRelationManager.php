<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Filament\Support\AssignCourseAction;
use App\Models\Course;
use App\Services\Courses\EnrollmentService;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Courses this student can access. */
class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    protected static ?string $title = 'Assigned courses';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->weight('bold'),
                TextColumn::make('pivot.assigned_at')->label('Assigned on')->dateTime('d M Y, h:i A'),
            ])
            ->headerActions([
                AssignCourseAction::make(fn () => $this->getOwnerRecord()),
            ])
            ->recordActions([
                Action::make('remove')->label('Remove access')->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                    ->action(fn (Course $record) => app(EnrollmentService::class)->remove($this->getOwnerRecord(), $record)),
            ]);
    }
}
