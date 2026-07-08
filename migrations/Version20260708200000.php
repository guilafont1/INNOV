<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme ROLE_ADMIN en ROLE_SUPER_ADMIN pour les comptes existants';
    }

    public function up(Schema $schema): void
    {
    }

    public function postUp(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, roles FROM user');

        foreach ($rows as $row) {
            $roles = json_decode((string) $row['roles'], true);
            if (!is_array($roles)) {
                continue;
            }

            $changed = false;
            $roles = array_map(static function (string $role) use (&$changed): string {
                if ($role === 'ROLE_ADMIN') {
                    $changed = true;

                    return 'ROLE_SUPER_ADMIN';
                }

                return $role;
            }, $roles);

            if ($changed) {
                $this->connection->update(
                    'user',
                    ['roles' => json_encode(array_values($roles))],
                    ['id' => $row['id']],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
