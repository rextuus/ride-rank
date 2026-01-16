<?php

namespace App\Enum;

enum ComparisonOutcome: string
{
    case WIN = 'win';
    case SKIP = 'skip';
}