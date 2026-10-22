<?php

namespace App\Services\Student;

use App\Enums\VideoProvider;
use App\Models\Video;

/**
 * Turns a stored video (provider + id/link) into an iframe URL for the player.
 * To add a provider (Bunny, VdoCipher…), add a branch here. Returns null when the provider isn't supported yet.
 */
class VideoEmbed
{
    public function url(Video $video): ?string
    {
        return match ($video->provider) {
            VideoProvider::YouTube => 'https://www.youtube-nocookie.com/embed/'.$this->youtubeId($video->provider_ref).'?rel=0&modestbranding=1',
            VideoProvider::Vimeo => 'https://player.vimeo.com/video/'.preg_replace('/\D/', '', $video->provider_ref),
            default => null,
        };
    }

    /** Accepts a bare id or any youtube.com / youtu.be link. */
    private function youtubeId(string $ref): string
    {
        if (preg_match('~(?:v=|youtu\.be/|embed/|live/)([A-Za-z0-9_-]{6,})~', $ref, $m)) {
            return $m[1];
        }

        return trim($ref);
    }
}
