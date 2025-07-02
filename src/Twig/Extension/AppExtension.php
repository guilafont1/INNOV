<?php

namespace App\Twig\Extension;


use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        $roleLabel = null;

        if ($user) {
            $roles = $user->getRoles();
            $mainRole = $roles[0] ?? null;

            $roleLabel = match ($mainRole) {
                'ROLE_ADMIN' => 'Admin',
                'ROLE_ENSEIGNANT' => 'Enseignant',
                'ROLE_ETUDIANT' => 'Étudiant',
                default => 'Utilisateur',
            };
        }

        return [
            'userRoleLabel' => $roleLabel,
        ];
    }
}
