<?php

declare(strict_types=1);

namespace App\Enum;

enum OperatingStatus: string
{
    case OPERATING_SINCE = 'Operating since';
    case REMOVED_OPERATED_FROM = 'Removed, Operated from to';
    case REMOVED_OPERATED_FROM_TO = 'Removed, Operated from to -';
    case REMOVED_OPERATED_FROM_LET = 'Removed, Operated from ≤ to';
    case REMOVED_IN_STORAGE_FROM_LET = 'Removed, In Storage from to ≤';
    case REMOVED_IN_STORAGE_FROM_TO = 'Removed, In Storage from to';
    case REMOVED_IN_STORAGE_FROM_TO_QUESTION = 'Removed, Operated from to ?';
    case SBNO_SINCE = 'SBNO since';
}
