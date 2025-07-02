<?php

namespace App\Entity;

use App\Repository\ProgressionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Cours;
use App\Entity\User;

#[ORM\Entity(repositoryClass: ProgressionRepository::class)]
class Progression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'progressions')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'progressions')]
    private ?Cours $cours = null;

    #[ORM\Column]
    private ?float $avancement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;

        return $this;
    }

    public function getAvancement(): ?float
    {
        return $this->avancement;
    }

    public function setAvancement(float $avancement): static
    {
        $this->avancement = $avancement;

        return $this;
    }
}
