<?php

namespace App\Console\Commands;

use App\Services\Tests\AttemptService;
use Illuminate\Console\Command;

/** Submits tests whose time is up, even if the student closed the browser. Scheduled every minute. */
class AutoSubmitAttempts extends Command
{
    protected $signature = 'attempts:auto-submit';

    protected $description = 'Submit unfinished test attempts whose time has run out';

    public function handle(AttemptService $attempts): int
    {
        $this->info("Auto-submitted {$attempts->autoSubmitExpired()} attempt(s).");

        return self::SUCCESS;
    }
}
