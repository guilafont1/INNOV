<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute read_at sur message pour les accusés de lecture';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message ADD read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message DROP read_at');
    }
}
