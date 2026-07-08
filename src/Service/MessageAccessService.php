<?php

namespace App\Service;

use App\Entity\User;
use App\Security\UserRole;

class MessageAccessService
{
    /**
     * @return array<int, User>
     */
    public function getEligibleContacts(User $user): array
    {
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

        uasort($contacts, static function (User $a, User $b): int {
            $nameA = ($a->getPrenom() ?? '') . ' ' . ($a->getNom() ?? '');
            $nameB = ($b->getPrenom() ?? '') . ' ' . ($b->getNom() ?? '');

            return strcasecmp($nameA, $nameB);
        });

        return $contacts;
    }

    public function canMessage(User $from, User $to): bool
    {
        $contacts = $this->getEligibleContacts($from);

        return isset($contacts[$to->getId()]);
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
