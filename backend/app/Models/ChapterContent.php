<?php

namespace App\Models;

use App\Enums\ContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One item in a chapter's content list. `contentable` is the actual material:
 * PdfDocument, Video, LiveClass, Note, Assignment or Test (practice).
 */
class ChapterContent extends Model
{
    protected $fillable = ['chapter_id', 'type', 'title', 'contentable_type', 'contentable_id', 'position'];

    protected function casts(): array
    {
        return ['type' => ContentType::class];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function contentable(): MorphTo
    {
        return $this->morphTo();
    }
}
