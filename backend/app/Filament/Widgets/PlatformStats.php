<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TestAttempt;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Dashboard numbers. */
class PlatformStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Students', User::count())->description(User::whereDate('created_at', today())->count().' registered today')->icon('heroicon-o-academic-cap'),
            Stat::make('Active courses', Course::where('is_active', true)->count())->description(Enrollment::count().' enrollments')->icon('heroicon-o-book-open'),
            Stat::make('Students without a course', User::doesntHave('courses')->count())->description('Waiting for you to assign a course')->color('warning')->icon('heroicon-o-clock'),
            Stat::make('Tests taken today', TestAttempt::whereDate('submitted_at', today())->count())->description(TestAttempt::whereNotNull('submitted_at')->count().' in total')->icon('heroicon-o-clipboard-document-check'),
        ];
    }
}
