<?php

namespace App\Jobs;

use App\Models\QuestionImport;
use App\Services\Imports\QuestionImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Reads a question file in the background (used by the AI reader, which takes minutes). */
class ProcessQuestionImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public int $importId) {}

    public function handle(QuestionImportService $service): void
    {
        if ($import = QuestionImport::find($this->importId)) {
            $service->process($import);
        }
    }
}
