<?php

namespace App\Enums;

/** Kinds of items that can be added to a chapter. Add a case here to support a new type. */
enum ContentType: string
{
    case Video = 'video';
    case LiveClass = 'live_class';
    case Pdf = 'pdf';
    case Note = 'note';
    case Assignment = 'assignment';
    case PracticeTest = 'practice_test';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Recorded lecture',
            self::LiveClass => 'Live class',
            self::Pdf => 'PDF notes',
            self::Note => 'Notes',
            self::Assignment => 'Assignment',
            self::PracticeTest => 'Practice test',
        };
    }
}
