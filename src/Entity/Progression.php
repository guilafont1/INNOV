<?php

namespace App\Entity;

use App\Repository\ProgressionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Chapitre;
use App\Entity\Cours;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ProgressionRepository::class)]
class Progression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'progressions')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'progressions')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Cours $cours = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Chapitre $dernierChapitre = null;

    #[ORM\Column]
    private ?float $avancement = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Chapitre>
     */
    #[ORM\ManyToMany(targetEntity: Chapitre::class)]
    #[ORM\JoinTable(
        name: 'progression_chapitre',
        joinColumns: [new ORM\JoinColumn(name: 'progression_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'chapitre_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private Collection $chapitresConsultees;

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

    public function getDernierChapitre(): ?Chapitre
    {
        return $this->dernierChapitre;
    }

    public function setDernierChapitre(?Chapitre $dernierChapitre): static
    {
        $this->dernierChapitre = $dernierChapitre;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Chapitre>
     */
    public function getChapitresConsultees(): Collection
    {
        return $this->chapitresConsultees;
    }

    public function addChapitreConsulte(Chapitre $chapitre): static
    {
        if (!$this->chapitresConsultees->contains($chapitre)) {
            $this->chapitresConsultees->add($chapitre);
        }

        return $this;
    }

    public function removeChapitreConsulte(Chapitre $chapitre): static
    {
        $this->chapitresConsultees->removeElement($chapitre);

        return $this;
    }

    public function __construct()
    {
        $this->chapitresConsultees = new ArrayCollection();
    }
}
