<?php

namespace App\Service\Rating;

enum UserMatchupState: string
{
    case ANONYMOUS = 'anonymous';
    case NEW_USER = 'new_user';
    case ESTABLISHED = 'established';
}
