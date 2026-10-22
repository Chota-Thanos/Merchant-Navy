<?php

namespace App\Enums;

/** How an uploaded file is turned into questions. */
enum ImportMethod: string
{
    /** Rule-based reader for well-structured PDFs: instant, free, exact. */
    case Pdf = 'pdf';
    /** AI reader (Claude) for any PDF layout. Slower; needs an API key. */
    case PdfAi = 'pdf_ai';
    /** Spreadsheet with one question per row. */
    case Excel = 'excel';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF (automatic reader)',
            self::PdfAi => 'PDF with AI',
            self::Excel => 'Excel / CSV',
        };
    }
}
