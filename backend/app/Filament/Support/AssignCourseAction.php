<?php

namespace App\Filament\Support;

use App\Models\Course;
use App\Models\User;
use App\Services\Courses\EnrollmentService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;

/**
 * "Assign courses" pop-up used everywhere a student gets courses: the courses the student does not have yet are listed
 * with tick boxes (with "select all"), the ones they already have are named above, and one button assigns the ticked ones.
 */
class AssignCourseAction
{
    /** @param  Closure(Action): User  $student  returns the student this action is for */
    public static function make(Closure $student): Action
    {
        return Action::make('assign')
            ->label('Assign course')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->modalHeading('Assign courses')
            ->modalDescription(function (Action $action) use ($student) {
                $have = $student($action)->courses()->orderBy('title')->pluck('title');

                return $have->isEmpty()
                    ? 'Tick the courses this student should get, then press Assign.'
                    : 'Already has: '.$have->implode(', ').'. Tick any more courses to give, then press Assign.';
            })
            ->modalSubmitActionLabel('Assign selected')
            ->modalWidth('lg')
            ->schema(fn (Action $action) => [
                CheckboxList::make('courses')
                    ->hiddenLabel()
                    ->options(fn () => Course::where('is_active', true)
                        ->whereNotIn('id', self::assignedIds($student($action)))
                        ->orderBy('title')->pluck('title', 'id'))
                    ->bulkToggleable()
                    ->searchable()
                    ->columns(1)
                    ->required(),
            ])
            ->action(function (array $data, Action $action) use ($student) {
                $user = $student($action);

                $given = 0;
                foreach (Course::findMany($data['courses']) as $course) {
                    $given += app(EnrollmentService::class)->assign($user, $course, auth('admin')->user()) ? 1 : 0;
                }

                Notification::make()->title("{$given} course(s) assigned — the student has been notified")->success()->send();
            });
    }

    /** @return list<int> */
    private static function assignedIds(User $student): array
    {
        return $student->enrollments()->pluck('course_id')->map(fn ($id) => (int) $id)->all();
    }
}
