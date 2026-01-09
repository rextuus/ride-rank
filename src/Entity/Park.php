<?php

namespace App\Entity;

use App\Common\Entity\Trait\NonUniqueIdentTrait;
use App\Common\Entity\Trait\RcdbEntityTrait;
use App\Repository\ParkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParkRepository::class)]
class Park
{
    use NonUniqueIdentTrait;
    use RcdbEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
