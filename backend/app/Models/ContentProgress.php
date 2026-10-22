<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A student marked a chapter content item as complete. */
class ContentProgress extends Model
{
    protected $table = 'content_progress';

    public $timestamps = false;

    protected $fillable = ['user_id', 'chapter_content_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
