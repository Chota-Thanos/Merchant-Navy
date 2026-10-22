<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\Course;
use App\Models\User;
use App\Services\Courses\EnrollmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
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
                Action::make('assign')->label('Assign course')->icon('heroicon-o-plus-circle')
                    ->schema([
                        Select::make('courses')->multiple()->required()->options(fn () => Course::where('is_active', true)->pluck('title', 'id')),
                    ])
                    ->action(function (array $data) {
                        /** @var User $student */
                        $student = $this->getOwnerRecord();
                        foreach (Course::findMany($data['courses']) as $course) {
                            app(EnrollmentService::class)->assign($student, $course, auth('admin')->user());
                        }
                        Notification::make()->title('Course assigned — the student has been notified')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('remove')->label('Remove access')->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                    ->action(fn (Course $record) => app(EnrollmentService::class)->remove($this->getOwnerRecord(), $record)),
            ]);
    }
}
