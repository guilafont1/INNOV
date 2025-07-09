<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, ForumPost>
     */
    #[ORM\OneToMany(targetEntity: ForumPost::class, mappedBy: 'auteur')]
    private Collection $forumPosts;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'expediteur')]
    private Collection $messages;

    /**
     * @var Collection<int, Progression>
     */
    #[ORM\OneToMany(targetEntity: Progression::class, mappedBy: 'user')]
    private Collection $progressions;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'createdBy')]
    private Collection $evaluations;

    /**
     * @var Collection<int, Cours>
     */
    #[ORM\OneToMany(targetEntity: Cours::class, mappedBy: 'createdBy')]
    private Collection $coursCreated;

    /**
     * @var Collection<int, Classe> Les classes où l'utilisateur est étudiant
     */
    #[ORM\ManyToMany(targetEntity: Classe::class, mappedBy: 'etudiants')]
    private Collection $classes;

    /**
     * @var Collection<int, Classe> Les classes où l'utilisateur est professeur
     */
    #[ORM\ManyToMany(targetEntity: Classe::class, mappedBy: 'professeurs')]
    private Collection $classesEnseignees;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $passwordUpdatedAt = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'etudiant')]
    private Collection $notes;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'professeur')]
    private Collection $notesAttribuees;

    public function __construct()
    {
        $this->forumPosts = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->progressions = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->coursCreated = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->classesEnseignees = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->notesAttribuees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, ForumPost>
     */
    public function getForumPosts(): Collection
    {
        return $this->forumPosts;
    }

    public function addForumPost(ForumPost $forumPost): static
    {
        if (!$this->forumPosts->contains($forumPost)) {
            $this->forumPosts->add($forumPost);
            $forumPost->setAuteur($this);
        }

        return $this;
    }

    public function removeForumPost(ForumPost $forumPost): static
    {
        if ($this->forumPosts->removeElement($forumPost)) {
            // set the owning side to null (unless already changed)
            if ($forumPost->getAuteur() === $this) {
                $forumPost->setAuteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setExpediteur($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getExpediteur() === $this) {
                $message->setExpediteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Progression>
     */
    public function getProgressions(): Collection
    {
        return $this->progressions;
    }

    public function addProgression(Progression $progression): static
    {
        if (!$this->progressions->contains($progression)) {
            $this->progressions->add($progression);
            $progression->setUser($this);
        }

        return $this;
    }

    public function removeProgression(Progression $progression): static
    {
        if ($this->progressions->removeElement($progression)) {
            // set the owning side to null (unless already changed)
            if ($progression->getUser() === $this) {
                $progression->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setCreatedBy($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getCreatedBy() === $this) {
                $evaluation->setCreatedBy(null);
            }
        }

        return $this;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getPasswordUpdatedAt(): ?\DateTimeInterface
    {
        return $this->passwordUpdatedAt;
    }

    public function setPasswordUpdatedAt(?\DateTimeInterface $passwordUpdatedAt): static
    {
        $this->passwordUpdatedAt = $passwordUpdatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Cours>
     */
    public function getCoursCreated(): Collection
    {
        return $this->coursCreated;
    }

    public function addCoursCreated(Cours $coursCreated): static
    {
        if (!$this->coursCreated->contains($coursCreated)) {
            $this->coursCreated->add($coursCreated);
            $coursCreated->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCoursCreated(Cours $coursCreated): static
    {
        if ($this->coursCreated->removeElement($coursCreated)) {
            // set the owning side to null (unless already changed)
            if ($coursCreated->getCreatedBy() === $this) {
                $coursCreated->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Classe>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClass(Classe $class): static
    {
        if (!$this->classes->contains($class)) {
            $this->classes->add($class);
            $class->addEtudiant($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): static
    {
        if ($this->classes->removeElement($class)) {
            $class->removeEtudiant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Classe>
     */
    public function getClassesEnseignees(): Collection
    {
        return $this->classesEnseignees;
    }

    public function addClassEnseignee(Classe $classEnseignee): static
    {
        if (!$this->classesEnseignees->contains($classEnseignee)) {
            $this->classesEnseignees->add($classEnseignee);
            $classEnseignee->addProfesseur($this);
        }

        return $this;
    }

    public function removeClassEnseignee(Classe $classEnseignee): static
    {
        if ($this->classesEnseignees->removeElement($classEnseignee)) {
            $classEnseignee->removeProfesseur($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setEtudiant($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getEtudiant() === $this) {
                $note->setEtudiant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotesAttribuees(): Collection
    {
        return $this->notesAttribuees;
    }

    public function addNotesAttribuee(Note $notesAttribuee): static
    {
        if (!$this->notesAttribuees->contains($notesAttribuee)) {
            $this->notesAttribuees->add($notesAttribuee);
            $notesAttribuee->setProfesseur($this);
        }

        return $this;
    }

    public function removeNotesAttribuee(Note $notesAttribuee): static
    {
        if ($this->notesAttribuees->removeElement($notesAttribuee)) {
            if ($notesAttribuee->getProfesseur() === $this) {
                $notesAttribuee->setProfesseur(null);
            }
        }

        return $this;
    }
}
