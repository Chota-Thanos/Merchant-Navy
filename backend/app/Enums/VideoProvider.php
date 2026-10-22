<?php

namespace App\Enums;

/** Where recorded lectures are hosted. Decide one before launch; others can stay for later. */
enum VideoProvider: string
{
    case YouTube = 'youtube';
    case Bunny = 'bunny';
    case Vimeo = 'vimeo';
    case VdoCipher = 'vdocipher';
}
