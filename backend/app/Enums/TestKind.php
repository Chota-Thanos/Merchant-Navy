<?php

namespace App\Enums;

enum TestKind: string
{
    /** Lives inside a chapter's content list. */
    case Practice = 'practice';
    /** Lives in the course curriculum between subjects and in the Mock Tests page. */
    case Mock = 'mock';
}
