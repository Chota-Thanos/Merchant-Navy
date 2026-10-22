<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stored on the private disk. Students only ever receive watermarked page images. */
class PdfDocument extends Model
{
    protected $fillable = ['file_path', 'page_count', 'processed_at'];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
