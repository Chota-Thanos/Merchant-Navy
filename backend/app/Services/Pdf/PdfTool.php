<?php

namespace App\Services\Pdf;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Runs tools/pdf_tool.py (PyMuPDF) to read, render and split PDFs.
 * Server setup:  pip install pymupdf   (set LMS_PYTHON if python is not on the PATH).
 */
class PdfTool
{
    /** @return array{title:string, pageCount:int, pages:array<int,array>} text of every page, rebuilt from glyph positions */
    public function extract(string $pdfPath): array
    {
        return $this->run(['extract', $pdfPath], 600);
    }

    /** Writes 1.png, 2.png… into `$dir`. */
    public function render(string $pdfPath, string $dir, int $dpi = 110): array
    {
        return $this->run(['render', $pdfPath, $dir, '--dpi', (string) $dpi], 600);
    }

    /** Copies pages `$first`..`$last` (1-based) into a new PDF. */
    public function split(string $pdfPath, string $outPdf, int $first, int $last): array
    {
        return $this->run(['split', $pdfPath, $outPdf, (string) $first, (string) $last], 120);
    }

    /** True when python and PyMuPDF can be started. */
    public function available(): bool
    {
        try {
            $process = new Process([config('lms.python'), '-c', 'import fitz']);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function run(array $args, int $timeout): array
    {
        $process = new Process([config('lms.python'), config('lms.pdf_tool'), ...$args]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (\Throwable $e) {
            throw new RuntimeException('Python could not be started ('.config('lms.python').'). Install Python and set LMS_PYTHON. '.$e->getMessage());
        }

        $result = json_decode($process->getOutput(), true);

        if (! $process->isSuccessful() || ! is_array($result) || isset($result['error'])) {
            throw new RuntimeException($result['error'] ?? 'PDF tool failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return $result;
    }
}
