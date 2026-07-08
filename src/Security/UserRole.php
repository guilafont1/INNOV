<?php

namespace App\Security;

/**
 * Rôles applicatifs MERJ Learn.
 *
 * Hiérarchie (security.yaml) :
 *   ROLE_SUPER_ADMIN → ROLE_ADMIN_ECOLE → ROLE_USER
 */
final class UserRole
{
    public const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const ADMIN_ECOLE = 'ROLE_ADMIN_ECOLE';
    public const ENSEIGNANT = 'ROLE_ENSEIGNANT';
    public const ETUDIANT = 'ROLE_ETUDIANT';
    public const USER = 'ROLE_USER';

    /** @deprecated Migré vers ROLE_SUPER_ADMIN */
    public const LEGACY_ADMIN = 'ROLE_ADMIN';

    public const ASSIGNABLE = [
        self::ETUDIANT,
        self::ENSEIGNANT,
        self::ADMIN_ECOLE,
        self::SUPER_ADMIN,
    ];

    /**
     * @param list<string> $roles
     */
    public static function isSuperAdmin(array $roles): bool
    {
        return in_array(self::SUPER_ADMIN, $roles, true)
            || in_array(self::LEGACY_ADMIN, $roles, true);
    }

    /**
     * @param list<string> $roles
     */
    public static function isSchoolAdmin(array $roles): bool
    {
        return self::isSuperAdmin($roles)
            || in_array(self::ADMIN_ECOLE, $roles, true);
    }

    /**
     * @param list<string> $roles
     */
    public static function label(array $roles): string
    {
        if (self::isSuperAdmin($roles)) {
            return 'Super administrateur';
        }
        if (in_array(self::ADMIN_ECOLE, $roles, true)) {
            return 'Administration école';
        }
        if (in_array(self::ENSEIGNANT, $roles, true)) {
            return 'Enseignant';
        }
        if (in_array(self::ETUDIANT, $roles, true)) {
            return 'Étudiant';
        }

        return 'Utilisateur';
    }

    /**
     * @param list<string> $roles
     */
    public static function primary(array $roles): ?string
    {
        foreach ([self::SUPER_ADMIN, self::LEGACY_ADMIN, self::ADMIN_ECOLE, self::ENSEIGNANT, self::ETUDIANT] as $role) {
            if (in_array($role, $roles, true)) {
                return $role === self::LEGACY_ADMIN ? self::SUPER_ADMIN : $role;
            }
        }

        return null;
    }
}
