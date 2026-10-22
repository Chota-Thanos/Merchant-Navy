<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduled jobs. On the server, one cron line runs them all:
|   * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
*/

// Submit tests whose time ran out (student closed the browser, lost connection…)
Schedule::command('attempts:auto-submit')->everyMinute()->withoutOverlapping();

// "Live class starting soon" notifications
Schedule::command('live-classes:remind')->everyMinute()->withoutOverlapping();

// Housekeeping
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('model:prune', ['--model' => [\App\Models\OtpCode::class]])->daily();
