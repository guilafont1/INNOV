<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\UserRole;

class MessageAccessService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function isGlobalViewer(User $user): bool
    {
        return UserRole::isSuperAdmin($user->getRoles());
    }

    /**
     * @return array<int, User>
     */
    public function getEligibleContacts(User $user): array
    {
        if ($this->isGlobalViewer($user)) {
            return $this->indexUsersById(
                $this->userRepository->findAllExcept($user),
            );
        }

        $contacts = [];

        $classes = [];
        foreach ($user->getClasses() as $classe) {
            $classes[] = $classe;
        }
        foreach ($user->getClassesEnseignees() as $classe) {
            $classes[] = $classe;
        }

        foreach ($classes as $classe) {
            foreach ($classe->getEtudiants() as $etudiant) {
                $contacts[$etudiant->getId()] = $etudiant;
            }
            foreach ($classe->getProfesseurs() as $professeur) {
                $contacts[$professeur->getId()] = $professeur;
            }
        }

        unset($contacts[$user->getId()]);

        return $this->sortContacts($contacts);
    }

    public function canMessage(User $from, User $to): bool
    {
        if ($from->getId() === $to->getId()) {
            return false;
        }

        if ($this->isGlobalViewer($from)) {
            return true;
        }

        $contacts = $this->getEligibleContacts($from);

        return isset($contacts[$to->getId()]);
    }

    public function canAccessThread(User $viewer, User $partner, ?User $with = null): bool
    {
        if ($with !== null) {
            if ($partner->getId() === $with->getId()) {
                return false;
            }

            if ($this->isGlobalViewer($viewer)) {
                return true;
            }

            return $viewer->getId() === $partner->getId() || $viewer->getId() === $with->getId();
        }

        if ($this->isGlobalViewer($viewer)) {
            return true;
        }

        return $this->canMessage($viewer, $partner);
    }

    public function getConversationDisplayName(User $userA, User $userB): string
    {
        return $this->getUserDisplayName($userA) . ' ↔ ' . $this->getUserDisplayName($userB);
    }

    /**
     * @param array<int, User> $contacts
     * @return array<int, User>
     */
    private function sortContacts(array $contacts): array
    {
        uasort($contacts, static function (User $a, User $b): int {
            $nameA = ($a->getPrenom() ?? '') . ' ' . ($a->getNom() ?? '');
            $nameB = ($b->getPrenom() ?? '') . ' ' . ($b->getNom() ?? '');

            return strcasecmp($nameA, $nameB);
        });

        return $contacts;
    }

    /**
     * @param list<User> $users
     * @return array<int, User>
     */
    private function indexUsersById(array $users): array
    {
        $contacts = [];
        foreach ($users as $user) {
            $contacts[$user->getId()] = $user;
        }

        return $this->sortContacts($contacts);
    }

    public function getUserDisplayName(User $user): string
    {
        return trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? ''));
    }

    public function getUserInitials(User $user): string
    {
        $prenom = $user->getPrenom() ?? '';
        $nom = $user->getNom() ?? '';

        return strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
    }

    /**
     * @return array{id: int, name: string, initials: string, email: string, role: string}
     */
    public function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $this->getUserDisplayName($user),
            'initials' => $this->getUserInitials($user),
            'email' => $user->getEmail() ?? '',
            'role' => $this->getRoleLabel($user),
        ];
    }

    private function getRoleLabel(User $user): string
    {
        return UserRole::label($user->getRoles());
    }
}
