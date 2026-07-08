<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\CalendrierRepository;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\UserRepository;
use App\Security\UserRole;

class PlanningPageService
{
    public function __construct(
        private PlanningAccessService $accessService,
        private ClasseRepository $classeRepository,
        private CoursRepository $coursRepository,
        private UserRepository $userRepository,
        private CalendrierRepository $calendrierRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPageContext(User $user): array
    {
        $role = $this->accessService->getRoleKey($user);
        $canCreate = $this->accessService->canCreate($user);
        $isAdminScope = $this->accessService->hasAdminPlanningScope($user);

        $classes = $isAdminScope
            ? $this->classeRepository->findBy([], ['nom' => 'ASC'])
            : ($role === 'enseignant'
                ? $this->classeRepository->findByProfesseur($user)
                : $this->classeRepository->findByEtudiant($user));

        $cours = $isAdminScope
            ? $this->coursRepository->findBy([], ['titre' => 'ASC'])
            : ($role === 'enseignant'
                ? $this->coursRepository->findForEnseignant($user)
                : $this->coursRepository->findByUser($user));

        $enseignants = $isAdminScope
            ? $this->userRepository->findByRole(UserRole::ENSEIGNANT)
            : [];

        $eyebrow = match ($role) {
            'super_admin' => 'Super administration',
            'admin_ecole' => 'Administration école',
            'enseignant' => 'Espace enseignant',
            default => 'Espace étudiant',
        };

        $planningRole = $isAdminScope ? 'admin' : $role;

        $exportRoute = 'planning_export_pdf';

        return [
            'planningRole' => $planningRole,
            'planningCanCreate' => $canCreate,
            'planningCanEdit' => $this->accessService->hasAdminPlanningScope($user),
            'planningEyebrow' => $eyebrow,
            'filterClasses' => $classes,
            'filterCours' => is_array($cours) ? $cours : [],
            'filterEnseignants' => $enseignants,
            'lieux' => $this->calendrierRepository->findDistinctLieux(),
            'planningExportRoute' => $exportRoute,
            'planningExportIcsRoute' => 'planning_export_ics',
        ];
    }
}
