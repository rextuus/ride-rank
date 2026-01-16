<?php

namespace App\Service\Player;

enum PlayerExperienceLevel: string
{
    case NEWBIE = 'newbie';          // 0–10
    case LOCAL = 'local';            // 11–50
    case ENTHUSIAST = 'enthusiast';  // 51–150
    case EXPERT = 'expert';          // 151+
}
