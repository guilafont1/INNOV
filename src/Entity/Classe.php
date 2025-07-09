<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, User> Les étudiants de la classe
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'classes')]
    #[ORM\JoinTable(name: 'classe_etudiants')]
    private Collection $etudiants;

    /**
     * @var Collection<int, User> Les professeurs de la classe
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'classesEnseignees')]
    #[ORM\JoinTable(name: 'classe_professeurs')]
    private Collection $professeurs;

    /**
     * @var Collection<int, Cours> Les cours de la classe
     */
    #[ORM\ManyToMany(targetEntity: Cours::class, inversedBy: 'classes')]
    #[ORM\JoinTable(name: 'classe_cours')]
    private Collection $cours;

    public function __construct()
    {
        $this->etudiants = new ArrayCollection();
        $this->professeurs = new ArrayCollection();
        $this->cours = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
     * @return Collection<int, User>
     */
    public function getEtudiants(): Collection
    {
        return $this->etudiants;
    }

    public function addEtudiant(User $etudiant): static
    {
        if (!$this->etudiants->contains($etudiant)) {
            $this->etudiants->add($etudiant);
        }

        return $this;
    }

    public function removeEtudiant(User $etudiant): static
    {
        $this->etudiants->removeElement($etudiant);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getProfesseurs(): Collection
    {
        return $this->professeurs;
    }

    public function addProfesseur(User $professeur): static
    {
        if (!$this->professeurs->contains($professeur)) {
            $this->professeurs->add($professeur);
        }

        return $this;
    }

    public function removeProfesseur(User $professeur): static
    {
        $this->professeurs->removeElement($professeur);

        return $this;
    }

    /**
     * @return Collection<int, Cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
        }

        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        $this->cours->removeElement($cour);

        return $this;
    }

    // Aliases pour la compatibilité
    public function addCours(Cours $cours): static
    {
        return $this->addCour($cours);
    }

    public function removeCours(Cours $cours): static
    {
        return $this->removeCour($cours);
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
