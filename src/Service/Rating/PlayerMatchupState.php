<?php

namespace App\Service\Rating;

enum PlayerMatchupState: string
{
    case CASUAL = 'casual';
    case NEW_PLAYER = 'new_player';
    case ESTABLISHED = 'established';
}
