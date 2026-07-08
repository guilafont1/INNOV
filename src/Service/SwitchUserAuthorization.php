<?php

namespace App\Service;

use App\Entity\User;
use App\Security\UserRole;

final class SwitchUserAuthorization
{
    public function canSwitchTo(?User $actor, User $target): bool
    {
        if ($actor === null || $actor->getId() === $target->getId()) {
            return false;
        }

        if (!UserRole::isSchoolAdmin($actor->getRoles())) {
            return false;
        }

        if (UserRole::isSuperAdmin($target->getRoles()) && !UserRole::isSuperAdmin($actor->getRoles())) {
            return false;
        }

        return true;
    }
}
