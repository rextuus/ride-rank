<?php

declare(strict_types=1);

namespace App\Enum;

enum OperatingStatus: string
{
    case OPERATING = 'Operating';
    case OPERATING_SINCE = 'Operating since';
    case OPERATING_SINCE_S = 'Operating since s';
    case OPERATING_SINCE_MINUS = 'Operating since -';
    case OPERATING_SINCE_LET = 'Operating since ≤';
    case OPERATING_SINCE_GET = 'Operating since ≥';
    case REMOVED_OPERATED_FROM_TO = 'Removed, Operated from to';
    case REMOVED_OPERATED_FROM_TO_MINUS = 'Removed, Operated from to -';
    case REMOVED_OPERATED_FROM_TO_S = 'Removed, Operated from to s';
    case REMOVED_OPERATED_FROM_LET_TO = 'Removed, Operated from ≤ to';
    case REMOVED_IN_STORAGE_FROM_TO_LET = 'Removed, In Storage from to ≤';
    case REMOVED_IN_STORAGE_FROM_TO = 'Removed, In Storage from to';
    case REMOVED_IN_STORAGE_FROM_TO_MINUS = 'Removed, In Storage from to -';
    case REMOVED_IN_STORAGE_FROM_TO_S = 'Removed, In Storage from to s';
    case REMOVED_IN_STORAGE_FROM_QUESTION_TO = 'Removed, In Storage from ? to';
    case REMOVED_IN_STORAGE_DURING= 'Removed, In Storage during';
    case REMOVED_OPERATED_FROM_S = 'Removed, Operated from s';
    case REMOVED_OPERATED_FROM_TO_QUESTION = 'Removed, Operated from to ?';
    case REMOVED_OPERATED_FROM_TO_LET = 'Removed, Operated from to ≤';
    case REMOVED_OPERATED_FROM_TO_GET = 'Removed, Operated from to ≥';
    case REMOVED_OPERATED_FROM_MINUS = 'Removed, Operated from -';
    case REMOVED_OPERATED_FROM_TO_GET_TO_S = 'Removed, Operated from to ≥ to s';
    case REMOVED_OPERATED_FROM_MINUS_TO_GET = 'Removed, Operated from - to ≥';
    case REMOVED_OPERATED_FROM_QUESTION_TO = 'Removed, Operated from ? to';
    case REMOVED_OPERATED_FROM_QUESTION_TO_MINUS = 'Removed, Operated from ? to -';
    case REMOVED_OPERATED_FROM_LET_TO_MINUS = 'Removed, Operated from ≤ to -';
    case REMOVED_OPERATED_FROM_GET_TO_MINUS = 'Removed, Operated from ≥ to -';
    case REMOVED_OPERATED_FROM_GET_TO = 'Removed, Operated from ≥ to';
    case REMOVED_OPERATED_FROM_LET_TO_GET = 'Removed, Operated from ≤ to ≥';
    case REMOVED_OPERATED_FROM_GET_TO_LET = 'Removed, Operated from ≥ to ≤';
    case REMOVED_OPERATED_FROM_GET_TO_S = 'Removed, Operated from ≥ to s';
    case REMOVED_OPERATED_FROM_S_TO = 'Removed, Operated from s to';
    case REMOVED_OPERATED_FROM_S_TO_S = 'Removed, Operated from s to s';
    case REMOVED_OPERATED_FROM_MINUS_TO = 'Removed, Operated from - to';
    case REMOVED_OPERATED_FROM_QUESTION_TO_LET = 'Removed, Operated from ? to ≤';
    case REMOVED_OPERATED_FROM_QUESTION_TO_GET = 'Removed, Operated from ? to ≥';
    case REMOVED_OPERATED_FROM_QUESTION_TO_S = 'Removed, Operated from ? to s';
    case REMOVED_OPERATED_FROM_S_TO_MINUS = 'Removed, Operated from s to -';
    case REMOVED_OPERATED_FROM_S_TO_QUESTION = 'Removed, Operated from s to ?';
    case REMOVED_OPERATED_FROM_MINUS_TO_MINUS = 'Removed, Operated from - to -';
    case REMOVED_OPERATED_FROM_MINUS_TO_QUESTION = 'Removed, Operated from - to ?';
    case REMOVED_OPERATED_FROM_MINUS_TO_LET = 'Removed, Operated from - to ≤';
    case REMOVED_OPERATED_FROM_LET_TO_LET = 'Removed, Operated from ≤ to ≤';
    case REMOVED_OPERATED_FROM_LET_TO_S = 'Removed, Operated from ≤ to s';
    case REMOVED_OPERATED_FROM_S_TO_LET = 'Removed, Operated from s to ≤';
    case REMOVED_OPERATED_FROM_LET = 'Removed, Operated from ≤';
    case REMOVED_OPERATED_FROM_LET_TO_QUESTION = 'Removed, Operated from ≤ to ?';
    case REMOVED_OPERATED_FROM_GET_TO_QUESTION = 'Removed, Operated from ≥ to ?';
    case REMOVED_OPERATED_FROM_GET_TO_GET = 'Removed, Operated from ≥ to ≥';
    case REMOVED_OPERATED_DURING = 'Removed, Operated during';
    case REMOVED_OPERATED = 'Removed, Operated';
    case REMOVED_SBNO_FROM_TO = 'Removed, SBNO from to';
    case REMOVED_SBNO_FROM_TO_MINUS = 'Removed, SBNO from to -';
    case REMOVED_SBNO_FROM_TO_LET = 'Removed, SBNO from to ≤';
    case REMOVED_SBNO_FROM_MINUS_TO = 'Removed, SBNO from - to';
    case REMOVED_SBNO_DURING = 'Removed, SBNO during';
    case REMOVED_SBNO_FROM_LET_TO_MINUS= 'Removed, SBNO from ≤ to -';
    case REMOVED_SBNO_FROM_LET_TO_MINUS_OPERATED = 'Removed, SBNO from ≤ to - Operated';
    case REMOVED_IN_STORAGE = 'Removed, In Storage';
    case SBNO_SINCE = 'SBNO since';
    case SBNO_SINCE_MINUS = 'SBNO since -';
    case SBNO_SINCE_LET = 'SBNO since ≤';
    case SBNO_SINCE_LET_TO_LET = 'SBNO since ≤ to ≤';
    case SBNO_FROM_LET_TO_LET = 'SBNO from ≤ to ≤';
    case SBNO_SINCE_GET = 'SBNO since ≥';
    case IN_STORAGE_SINCE = 'In Storage since';
    case UNDER_CONSTRUCTION_OPENING = 'Under Construction opening';
}
