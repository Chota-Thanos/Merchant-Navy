<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    /** Submitted by the system because the deadline passed. */
    case AutoSubmitted = 'auto_submitted';
}
