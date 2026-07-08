<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Security\UserRole;
use App\Service\DatabaseHealthChecker;
use App\Service\MessageUnreadService;
use App\Service\SwitchUserAuthorization;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Symfony\Bundle\SecurityBundle\Security;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private Security $security;
    private DatabaseHealthChecker $databaseHealthChecker;
    private SwitchUserAuthorization $switchUserAuthorization;
    private MessageUnreadService $messageUnreadService;

    public function __construct(
        Security $security,
        DatabaseHealthChecker $databaseHealthChecker,
        SwitchUserAuthorization $switchUserAuthorization,
        MessageUnreadService $messageUnreadService,
    ) {
        $this->security = $security;
        $this->databaseHealthChecker = $databaseHealthChecker;
        $this->switchUserAuthorization = $switchUserAuthorization;
        $this->messageUnreadService = $messageUnreadService;
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        $roleLabel = null;
        $messagesUnreadCount = 0;

        if ($user instanceof User) {
            $roleLabel = $this->getRoleLabel($user);
            $messagesUnreadCount = $this->messageUnreadService->countUnread($user);
        }

        return [
            'userRoleLabel' => $roleLabel,
            'messagesUnreadCount' => $messagesUnreadCount,
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('time_ago', [$this, 'formatTimeAgo']),
            new TwigFilter('planning_short_date', [$this, 'formatPlanningShortDate']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('user_initials', [$this, 'getUserInitials']),
            new TwigFunction('user_full_name', [$this, 'getUserFullName']),
            new TwigFunction('role_badge_class', [$this, 'getRoleBadgeClass']),
            new TwigFunction('user_role_label', [$this, 'getUserRoleLabelForUser']),
            new TwigFunction('route_is_active', [$this, 'isRouteActive']),
            new TwigFunction('db_is_connected', [$this->databaseHealthChecker, 'isConnected']),
            new TwigFunction('can_switch_to_user', [$this, 'canSwitchToUser']),
        ];
    }

    public function formatTimeAgo(?\DateTimeInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $date->getTimestamp();

        if ($diff < 60) {
            return 'à l\'instant';
        }
        if ($diff < 3600) {
            $m = (int) floor($diff / 60);
            return 'il y a ' . $m . ' min';
        }
        if ($diff < 86400) {
            $h = (int) floor($diff / 3600);
            return 'il y a ' . $h . ' h';
        }
        if ($diff < 604800) {
            $d = (int) floor($diff / 86400);
            return 'il y a ' . $d . ' j';
        }

        return $date->format('d/m/Y');
    }

    public function formatPlanningShortDate(?\DateTimeInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        $jours = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
        $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

        $j = (int) $date->format('w');
        $m = (int) $date->format('n') - 1;

        return $jours[$j] . ' ' . (int) $date->format('j') . ' ' . $mois[$m] . ' · ' . $date->format('H:i');
    }

    public function getUserInitials(?User $user): string
    {
        if ($user === null) {
            return '?';
        }

        $prenom = mb_substr($user->getPrenom() ?? '', 0, 1);
        $nom = mb_substr($user->getNom() ?? '', 0, 1);

        if ($prenom === '' && $nom === '') {
            return mb_strtoupper(mb_substr($user->getEmail() ?? 'U', 0, 1));
        }

        return mb_strtoupper($prenom . $nom);
    }

    public function getUserFullName(?User $user): string
    {
        if ($user === null) {
            return '';
        }

        $name = trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? ''));
        return $name !== '' ? $name : ($user->getEmail() ?? '');
    }

    public function getRoleBadgeClass(?string $role): string
    {
        return match ($role) {
            UserRole::SUPER_ADMIN, 'Super administrateur' => 'badge-role--super-admin',
            UserRole::ADMIN_ECOLE, 'Administration école' => 'badge-role--admin-ecole',
            UserRole::ENSEIGNANT, 'Enseignant' => 'badge-role--enseignant',
            UserRole::ETUDIANT, 'Étudiant' => 'badge-role--etudiant',
            default => 'badge-role--etudiant',
        };
    }

    public function getUserRoleLabelForUser(?User $user): string
    {
        if ($user === null) {
            return '';
        }

        return UserRole::label($user->getRoles());
    }

    public function isRouteActive(string $currentRoute, string|array $prefixes): bool
    {
        $list = is_array($prefixes) ? $prefixes : [$prefixes];

        foreach ($list as $prefix) {
            if ($currentRoute === $prefix || str_starts_with($currentRoute, $prefix . '_')) {
                return true;
            }
        }

        return false;
    }

    private function getRoleLabel(User $user): string
    {
        return UserRole::label($user->getRoles());
    }

    public function canSwitchToUser(?User $target): bool
    {
        if (!$target instanceof User) {
            return false;
        }

        $actor = $this->security->getUser();

        return $actor instanceof User
            && $this->switchUserAuthorization->canSwitchTo($actor, $target);
    }
}
