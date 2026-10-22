<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    protected $fillable = ['subject_id', 'title', 'position'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(ChapterContent::class)->orderBy('position');
    }
}
