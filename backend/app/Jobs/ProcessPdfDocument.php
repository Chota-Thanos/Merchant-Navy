<?php

namespace App\Jobs;

use App\Models\PdfDocument;
use App\Services\Pdf\PdfProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/** Renders a freshly uploaded PDF to page images in the background. */
class ProcessPdfDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 360;

    public function __construct(public int $pdfDocumentId) {}

    public function handle(PdfProcessor $processor): void
    {
        $document = PdfDocument::find($this->pdfDocumentId);
        if (! $document) {
            return;
        }

        try {
            $processor->process($document);
        } catch (\Throwable $e) {
            // Don't break the admin's save. The student sees "being prepared" until this succeeds.
            Log::error("PDF #{$document->id} could not be processed: {$e->getMessage()}");
        }
    }
}
