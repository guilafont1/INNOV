<?php

namespace App\Service;

use App\Entity\Calendrier;
use App\Entity\User;
use App\Security\UserRole;

class PlanningAccessService
{
    public function getRoleKey(User $user): string
    {
        $roles = $user->getRoles();

        if (UserRole::isSuperAdmin($roles)) {
            return 'super_admin';
        }
        if (in_array(UserRole::ADMIN_ECOLE, $roles, true)) {
            return 'admin_ecole';
        }
        if (in_array(UserRole::ENSEIGNANT, $roles, true)) {
            return 'enseignant';
        }

        return 'etudiant';
    }

    public function hasAdminPlanningScope(User $user): bool
    {
        return in_array($this->getRoleKey($user), ['super_admin', 'admin_ecole'], true);
    }

    public function canCreate(User $user): bool
    {
        return $this->hasAdminPlanningScope($user);
    }

    public function canEditEvent(User $user, Calendrier $event): bool
    {
        return $this->hasAdminPlanningScope($user);
    }

    public function canViewEvent(User $user, Calendrier $event): bool
    {
        // La visibilité est garantie par le repository ; double vérification sur l'entité.
        return true;
    }

    public function canDeleteEvent(User $user, Calendrier $event): bool
    {
        return $this->canEditEvent($user, $event);
    }

    public function formatUserName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $name = trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? ''));

        return $name !== '' ? $name : $user->getEmail();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeEvent(Calendrier $event, User $viewer): array
    {
        $editable = $this->canEditEvent($viewer, $event);
        $start = $event->getDateDebut();
        $end = $event->getDateFin();

        if ($end === null && $start !== null) {
            $end = (clone $start)->modify('+2 hours');
        }

        $enseignant = $event->getEnseignant();
        $classe = $event->getClasse();
        $cours = $event->getCours();

        return [
            'id' => $event->getId(),
            'title' => $event->getTitre(),
            'start' => $start?->format('Y-m-d\TH:i:s'),
            'end' => $end?->format('Y-m-d\TH:i:s'),
            'editable' => $editable,
            'classNames' => ['fc-event--' . ($event->getType() ?? 'autre')],
            'extendedProps' => [
                'type' => $event->getType(),
                'lieu' => $event->getLieu(),
                'description' => $event->getDescription(),
                'coursId' => $cours?->getId(),
                'coursTitre' => $cours?->getTitre(),
                'classeId' => $classe?->getId(),
                'classeNom' => $classe?->getNom(),
                'enseignantId' => $enseignant?->getId(),
                'enseignantNom' => $this->formatUserName($enseignant),
                'editable' => $editable,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateEventData(array $data, bool $partial = false): array
    {
        $errors = [];

        if (!$partial && empty(trim((string) ($data['titre'] ?? '')))) {
            $errors['titre'] = 'Le titre est requis.';
        }

        if (isset($data['type']) && $data['type'] !== '' && !in_array($data['type'], ['cours', 'examen', 'reunion', 'autre'], true)) {
            $errors['type'] = 'Type d\'événement invalide.';
        }

        $start = $data['start'] ?? $data['date_debut'] ?? null;
        $end = $data['end'] ?? $data['date_fin'] ?? null;

        if (!$partial && !$start) {
            $errors['start'] = 'La date de début est requise.';
        }

        if ($start && $end) {
            try {
                $dtStart = new \DateTime($start);
                $dtEnd = new \DateTime($end);
                if ($dtEnd <= $dtStart) {
                    $errors['end'] = 'La date de fin doit être postérieure à la date de début.';
                }
            } catch (\Exception) {
                $errors['start'] = 'Format de date invalide.';
            }
        }

        return $errors;
    }
}
