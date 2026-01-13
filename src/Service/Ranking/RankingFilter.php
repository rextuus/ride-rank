<?php

declare(strict_types=1);

namespace App\Service\Ranking;

use App\Entity\User;

class RankingFilter
{

    public function __construct(
        public User $user,
        public int $limit = 10,
        public int $offset = 0,
        public int $minimumGames = 1
    )
    {
    }
}
