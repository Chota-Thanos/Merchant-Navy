<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Scheduled live lecture. Provider details (Agora) are optional until live streaming is set up. */
class LiveClass extends Model
{
    protected $fillable = ['provider', 'starts_at', 'duration_minutes', 'provider_data', 'recording_video_id', 'reminder_sent_at'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'provider_data' => 'array',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function recording(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'recording_video_id');
    }

    /** upcoming | live | ended, worked out from the clock. */
    public function status(): string
    {
        $end = $this->starts_at->copy()->addMinutes($this->duration_minutes);

        return match (true) {
            now()->lt($this->starts_at) => 'upcoming',
            now()->lt($end) => 'live',
            default => 'ended',
        };
    }
}
